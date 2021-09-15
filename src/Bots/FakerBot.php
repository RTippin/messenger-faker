<?php

namespace RTippin\MessengerFaker\Bots;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Str;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use Throwable;

class FakerBot extends BotActionHandler
{
    /**
     * Faker commands we allow and if they have counts.
     */
    const FakerCommands = [
        'audio' => true,
        'document' => true,
        'image' => true,
        'knock' => false,
        'message' => true,
        'random' => true,
        'react' => true,
        'system' => true,
        'typing' => false,
        'video' => true,
    ];

    /**
     * The bots settings.
     *
     * @return array
     */
    public static function getSettings(): array
    {
        return [
            'alias' => 'faker',
            'description' => 'Access our underlying messenger faker commands. Eg: [ !faker {action} {count?} {delay?}]',
            'name' => 'Messenger Faker Commands',
            'unique' => true,
            'triggers' => ['!faker'],
            'match' => 'starts:with:caseless',
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $options = $this->parseOptions();

        if (! is_null($options[0])) {
            $this->composer()->emitTyping()->message("Faker initiating. Sending $options[1] $options[0] actions with a $options[2] second delay.");

            if (! self::isTesting()) {
                sleep(3);

                $this->handleCommand($options[0], $options[1], $options[2]);

                sleep(1);
            }

            $this->composer()->emitTyping()->message('Faker actions completed!');

            return;
        }

        $this->sendInvalidSelectionMessage();

        $this->releaseCooldown();
    }

    /**
     * @throws Throwable
     */
    private function sendInvalidSelectionMessage(): void
    {
        $this->composer()->emitTyping()->message('Please select a valid choice, eg: ( !faker {action} {count?} {delay?} )');
        $this->composer()->message('Available actions: [audio, document, image, knock, message, random, react, system, typing, video]');
    }

    /**
     * @param  string  $command
     * @param  int  $count
     * @param  int  $delay
     */
    private function handleCommand(string $command, int $count, int $delay): void
    {
        $options = [
            'thread' => $this->thread->id,
        ];

        if (self::FakerCommands[$command] === true) {
            $options['--count'] = $count;
            $options['--delay'] = $delay;
        }

        Artisan::call('messenger:faker:'.$command, $options);
    }

    /**
     * @return array
     */
    private function parseOptions(): array
    {
        //['command', 'count', 'delay']
        $options = [null, 5, 1];
        $choices = explode(' ', Str::lower(
            trim(
                Str::remove($this->matchingTrigger, $this->message->body, false)
            )
        ));

        if (array_key_exists($choices[0] ?? 'invalid', self::FakerCommands)) {
            $options[0] = $choices[0];
        }

        if (is_numeric($choices[1] ?? false)
            && $choices[1] >= 1
            && $choices[1] <= 50) {
            $options[1] = (int) $choices[1];
        }

        if (is_numeric($choices[2] ?? false)
            && $choices[2] >= 0
            && $choices[2] <= 5) {
            $options[2] = (int) $choices[2];
        }

        return $options;
    }
}
