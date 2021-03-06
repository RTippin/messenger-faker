<?php

namespace RTippin\MessengerFaker\Commands;

use Exception;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use RTippin\MessengerFaker\MessengerFaker;
use Throwable;

class ImageCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'messenger:faker:image 
                                            {thread? : ID of the thread you want to seed. Random if not set}
                                            {--count=1 : Number of image messages to send}
                                            {--delay=3 : Delay between each image message being sent}
                                            {--admins : Only use admins to send image messages if group thread}
                                            {--local : Pick a random image stored locally under storage/faker/images/}
                                            {--url= : Set the path/URL we grab an image from. Default uses unsplash}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make participants send image messages. Will also emit typing and mark read.';

    /**
     * Execute the console command.
     *
     * @param MessengerFaker $faker
     * @return void
     * @throws Throwable
     */
    public function handle(MessengerFaker $faker): void
    {
        try {
            $faker->setThreadWithId($this->argument('thread'), $this->option('admins'))
                ->setDelay($this->option('delay'));
        } catch (ModelNotFoundException $e) {
            $this->error('Thread not found.');

            return;
        }

        if ($this->option('local')) {
            $message = 'a random image from '.config('messenger-faker.paths.images');
        } else {
            $message = is_null($this->option('url')) ? config('messenger-faker.default_image_url') : $this->option('url');
        }
        $this->line('');
        $this->info("Found {$faker->getThreadName()}, now messaging images...");
        $this->info("Using {$message}");
        $this->line('');
        $bar = $this->output->createProgressBar($this->option('count'));
        $bar->start();

        try {
            for ($x = 1; $x <= $this->option('count'); $x++) {
                $faker->image(
                    $this->option('count') <= $x,
                    $this->option('local'),
                    $this->option('url')
                );
                $bar->advance();
            }
        } catch (Exception $e) {
            $this->line('');
            $this->line('');
            $this->error($e->getMessage());

            return;
        }

        $bar->finish();
        $this->line('');
        $this->line('');
        $this->info("Finished sending {$this->option('count')} image messages to {$faker->getThreadName()}!");
        $this->line('');
    }
}
