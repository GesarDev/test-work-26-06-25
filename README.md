Тестовое задание

# Задание 1
## Описание
> В БД (MySQL 8+) есть таблица user_scores со столбцами id, user_id, ts, score. Напишите запрос, возвращающий top-10 и bottom-10 user_id по сумме score за 7 суток, с указанием ранга и сортировкой по сумме score + место конкретного user_id в получившейся общей рейтинговой таблице. Предложите решения по оптимизации такого расчета (не обязательно SQL) в реальном времени для нагруженной системы.

### Запрос на получение первых и последних 10 записей за последние 7 суток

```sql
WITH recent_scores AS (SELECT user_id,
                              SUM(score) AS total_score
                       FROM user_scores
                       WHERE ts >= NOW() - INTERVAL 7 DAY
                       GROUP BY user_id),
     ranked_users AS (SELECT user_id,
                             total_score,
                             RANK() OVER (ORDER BY total_score DESC) AS rank_desc,
                             RANK() OVER (ORDER BY total_score)      AS rank_asc
                      FROM recent_scores)
SELECT *
FROM ranked_users
WHERE rank_desc <= 10
   OR rank_asc <= 10
ORDER BY total_score DESC;
```

### Получение места конкретного user_id за последние 7 суток

```sql
WITH recent_scores AS (
    SELECT user_id, SUM(score) AS total_score
    FROM user_scores
    WHERE ts >= NOW() - INTERVAL 7 DAY
    GROUP BY user_id
), ranked_users AS (
    SELECT user_id, total_score,
           RANK() OVER (ORDER BY total_score)  AS rank_asc
    FROM recent_scores
)
SELECT *
FROM ranked_users
WHERE user_id = :user_id -- Тут будет нужно указать user_id, для которого нужно получить место, например, 123
ORDER BY total_score DESC;
```

## Оптимизация расчета в реальном времени
### Предложения по оптимизации
1. Нужно добавить индексы на столбцы `ts` и `user_id` в таблице `user_scores`, чтобы ускорить фильтрацию и группировку данных.
2. Рассмотреть возможность аггрегирования данных в отдельную таблицу, которая будет обновляться периодически (например, раз в час), чтобы уменьшить нагрузку на основную таблицу. Либо использовать Debezium для отслеживания изменений в реальном времени.
3. Использовать кеширование результатов, чтобы избежать повторных вычислений. Например, Redis или Memcached могут быть использованы для хранения результатов запросов.


# Задание 2
## Описание

> В БД есть три таблицы:
> 1) books: id (int), title (varchar(255)), description (varchar(255)), pages_count(int), rowid(binary(16))
> 2) users: id (int), name (varchar(255)), rowid(binary(16))
> 3) bookmarks: id (int), user_id (int, внешний ключ на users), book_id (int, внешний ключ на books), bookmark (varchar(255)), rowid(binary(16))
>
> Напишите примерный код на Laravel, который будет возвращать данные по всем закладкам пользователя, включая информацию о названии и описании книги, в формате json-ответа для REST API. Необязательно писать реальный код - достаточно ключевых моментов, которые можно сопроводить текстовым пояснением.


## Решение

Первым делом необходимо создать модели для каждой из таблиц: `Book`, `User` и `Bookmark`.

### Модель `Book`
Является представлением таблицы `books` и содержит необходимые поля.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Book extends Model
{
    protected $fillable = [
        'title', 
        'description', 
        'pages_count', 
        'rowid',
    ];

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class, 'book_id');
    }
}
```

### Модель `User`
Аналогично модели `Book`, представляет таблицу `users`.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class User extends Model
{
    protected $table = 'users';
    protected $fillable = [
        'name',
        'rowid',
    ];

    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class, 'user_id');
    }
}
```

### Модель `Bookmark`
Модель для таблицы `bookmarks`, которая связывает пользователей и книги.

```php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Bookmark extends Model
{
    protected $fillable = [
        'user_id',
        'book_id',
        'bookmark',
        'rowid',
    ];

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class, 'book_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
```

В следующем шаге мы реализуем Resource-классы, для сериализации данных.
Ресурсы позволяют контролировать формат JSON-ответа, который будет возвращаться клиенту.

## Resource-класс для закладок
```php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookmarkResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'book' => new BookResource($this->book),
            'bookmark' => $this->bookmark,
        ];
    }
}
```

## Resource-класс для книги
```php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class BookResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'title' => $this->title,
            'description' => $this->description,
            'pages_count' => $this->pages_count,
        ];
    }
}
```


## Контроллер для получения закладок пользователя
```php
namespace App\Http\Controllers;

use App\Http\Resources\BookmarkResource;
use App\Models\User;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class BookmarkController extends Controller
{
    public function listForUser(User $user): AnonymousResourceCollection
    {
        $bookmarks = $user->bookmarks()->with('book')->get();

        return BookmarkResource::collection($bookmarks);
    }
}
```


## Маршруты
В файле `routes/api.php` необходимо добавить маршрут для получения закладок пользователя.

```php
use App\Http\Controllers\BookmarkController;
use Illuminate\Support\Facades\Route;

Route::get('/users/{user}/bookmarks', [BookmarkController::class, 'listForUser'])
    ->name('users.bookmarks.list');
```

Таким образом мы получим все закладки пользователя  в формате JSON, включая информацию о названии и описании книги. При использовании индексов результат будет возвращаться быстро, даже при большом количестве данных в таблицах, ~50мс на HTTP запрос без использования кеширования.


# Задание 3
## Описание

> В БД есть таблицы из задания номер 2 + таблица свойств пользователей user_properties, в которой хранится user_id, key (например, email или phone) и value (значение соответствующего ключа). Напишите код на Laravel дл получения всех пользователей с их имейлами и номерами телефона, которые читают книгу с id 123 (то есть пользователи, у которых есть закладка в этой книге). Подумайте, как можно оптимизировать код, если предположить, что в таблице пользователей может быть 10 миллионов строк.

Следующий код представляет собой решение задачи. Он является частью контроллера `BookController`. Рекомендации по оптимизации:
1) Добавление индексов на столбцы `bookmarks.book_id`, `user_properties.user_id` и `user_properties.key`, чтобы ускорить выполнение запроса.
2) Кеширование результатов запроса, любым движком кеширования.
3) Рассмотреть возможность использования пагинации, если количество пользователей слишком велико.
4) Рассмотреть возможности агрегирования данных в отдельную таблицу, которая будет обновляться периодически, чтобы уменьшить нагрузку на основную таблицу. Обновление данных можно реализовать посредством очередей Laravel или использованием иструмента Debezium.

В данном примере уже добавлены индексы на необходимые столбцы. При таблице `users` в 10 миллионов строк, запрос будет выполняться достаточно быстро ~100мс на HTTP запрос.

```php
public function listUsersByBook(Book $book): JsonResponse
{
    $bookId = $book->id;
    $users = \DB::table('users')
        ->select([
            'id' => 'users.id',
            'name' => 'users.name',
            \DB::raw("MAX(CASE WHEN user_properties.key = 'email' THEN user_properties.value END) as email"),
            \DB::raw("MAX(CASE WHEN user_properties.key = 'phone' THEN user_properties.value END) as phone"),
        ])
        ->leftJoin('bookmarks', 'users.id', '=', 'bookmarks.user_id')
        ->leftJoin('user_properties', 'users.id', '=', 'user_properties.user_id')
        ->where('bookmarks.book_id', '=', $bookId)
        ->whereIn('user_properties.key',
            [
                'email',
                'phone',
            ]
        )
        ->groupBy('users.id', 'users.name')
    ->get();

    return response()->json($users);
}
```

# Итог
Прикладываю исходный код решения заданий, так же мной была написана команда для генерации csv-файлов с данными для тестирования. Которые можно импортировать в MySQL и протестировать запросы. 
Находится по пути `app/Console/Commands/GenerateTenMillionsRecordsCommand.php`
Запуск осуществляется посредством ввода команды `php artisan ten-millions:generate`
