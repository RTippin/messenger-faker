<?php

namespace RTippin\MessengerFaker\Bots;

use Illuminate\Support\Facades\Artisan;
use RTippin\Messenger\Actions\Bots\BotActionHandler;
use RTippin\Messenger\MessengerBots;
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
            'match' => MessengerBots::MATCH_STARTS_WITH_CASELESS,
        ];
    }

    /**
     * @throws Throwable
     */
    public function handle(): void
    {
        $options = $this->parseOptions();

        if (is_null($options)) {
            $this->sendInvalidSelectionMessage();

            $this->releaseCooldown();

            return;
        }

        $this->composer()->emitTyping()->message(
            "Faker initiating. Sending $options[1] $options[0] actions with a $options[2] second delay."
        );

        if (! self::isTesting()) {
            sleep(3);

            $this->handleCommand(...$options);

            sleep(1);
        }

        $this->composer()->emitTyping()->message('Faker actions completed!');
    }

    /**
     * @throws Throwable
     */
    private function sendInvalidSelectionMessage(): void
    {
        $this->composer()->emitTyping()->message(
            'Please select a valid choice, eg: ( !faker {action} {count?} {delay?} )'
        );
        $this->composer()->message(
            'Available actions: [audio, document, image, knock, message, random, react, system, typing, video]'
        );
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
     * @return array|null
     */
    private function parseOptions(): ?array
    {
        $choices = $this->getParsedWords(true);

        if (is_null($choices)
            || ! array_key_exists($choices[0], self::FakerCommands)) {
            return null;
        }

        return [
            $choices[0],
            $this->setCount($choices),
            $this->setDelay($choices),
        ];
    }

    /**
     * @param  array  $choices
     * @return int
     */
    private function setCount(array $choices): int
    {
        if (is_numeric($choices[1] ?? false)
            && $choices[1] >= 1
            && $choices[1] <= 50) {
            return (int) $choices[1];
        }

        return 5;
    }

    /**
     * @param  array  $choices
     * @return int
     */
    private function setDelay(array $choices): int
    {
        if (is_numeric($choices[2] ?? false)
            && $choices[2] >= 0
            && $choices[2] <= 5) {
            return (int) $choices[2];
        }

        return 1;
    }
}
