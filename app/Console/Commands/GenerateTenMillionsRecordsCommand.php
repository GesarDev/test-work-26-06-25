<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class GenerateTenMillionsRecordsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ten-millions:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Генерация десяти миллионов юзеров для проверки производительности';

    public function handle(): int
    {
        $usersCount = 10_000_000;
        $booksCount = 10_000;
        $batchSize = 100_000;

        // Файлы
        $usersFile = fopen(storage_path('app/users.csv'), 'w');
        $booksFile = fopen(storage_path('app/books.csv'), 'w');
        $bookmarksFile = fopen(storage_path('app/bookmarks.csv'), 'w');
        $userPropsFile = fopen(storage_path('app/user_properties.csv'), 'w');

        // --- Книги ---
        $this->info('Генерация книг...');
        $bar = $this->output->createProgressBar($booksCount);
        for ($i = 1; $i <= $booksCount; $i++) {
            $rowid = bin2hex(random_bytes(16));
            fputcsv($booksFile, [$i, "Book $i", "Description $i", rand(100, 1000), $rowid]);
            $bar->advance();
        }
        $bar->finish();
        $this->newLine();

        // --- Пользователи, user_properties, bookmarks ---
        $this->info('Генерация пользователей, свойств и закладок...');
        $bar = $this->output->createProgressBar($usersCount);

        $bookmarkId = 1;
        for ($start = 1; $start <= $usersCount; $start += $batchSize) {
            $end = min($start + $batchSize - 1, $usersCount);

            for ($i = $start; $i <= $end; $i++) {
                // Users
                $rowid = bin2hex(random_bytes(16));
                fputcsv($usersFile, [$i, "User $i", $rowid]);

                // User properties
                fputcsv($userPropsFile, [$i, 'email', "user{$i}@example.com"]);
                if (rand(0, 1)) {
                    fputcsv($userPropsFile, [$i, 'phone', sprintf('+7%09d', $i)]);
                }

                // Bookmarks (2-5 на пользователя)
                $bookmarksCount = rand(2, 5);
                $usedBooks = [];
                for ($j = 0; $j < $bookmarksCount; $j++) {
                    do {
                        $bookId = rand(1, $booksCount);
                    } while (in_array($bookId, $usedBooks));
                    $usedBooks[] = $bookId;
                    $bRowid = bin2hex(random_bytes(16));
                    fputcsv($bookmarksFile, [$bookmarkId++, $i, $bookId, "Bookmark for book $bookId", $bRowid]);
                }
                $bar->advance();
            }
        }
        $bar->finish();
        $this->newLine();

        fclose($usersFile);
        fclose($booksFile);
        fclose($bookmarksFile);
        fclose($userPropsFile);

        $this->info('Генерация завершена!');

        return Command::SUCCESS;
    }
}
