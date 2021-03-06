<?php

namespace RTippin\MessengerFaker;

use Exception;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;

trait FakerFiles
{
    /**
     * @param bool $local
     * @param string|null $url
     * @return array
     * @throws Exception
     */
    private function getImage(bool $local, ?string $url): array
    {
        if (static::$isTesting) {
            return [UploadedFile::fake()->image('test.jpg'), 'test.jpg'];
        }

        if ($local) {
            $path = config('messenger-faker.paths.images');
            $images = File::files($path);

            if (! count($images)) {
                $this->throwFailedException("No images found within $path");
            }

            $file = Arr::random($images, 1)[0];
            $name = $file->getFilename();
        } else {
            $name = uniqid();
            $file = '/tmp/'.$name;
            file_put_contents($file,
                Http::timeout(30)->get(is_null($url)
                    ? config('messenger-faker.default_image_url')
                    : $url
                )->body()
            );
        }

        return [new UploadedFile($file, $name), $file];
    }

    /**
     * @param string|null $url
     * @return array
     * @throws Exception
     */
    private function getDocument(?string $url): array
    {
        if (static::$isTesting) {
            return [UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'), 'test.pdf'];
        }

        if (! is_null($url)) {
            $name = uniqid();
            $file = '/tmp/'.$name;
            file_put_contents($file, Http::timeout(30)->get($url)->body());
        } else {
            $path = config('messenger-faker.paths.documents');
            $documents = File::files($path);

            if (! count($documents)) {
                $this->throwFailedException("No documents found within $path");
            }

            $file = Arr::random($documents, 1)[0];
            $name = $file->getFilename();
        }

        return [new UploadedFile($file, $name), $file];
    }

    /**
     * @param string|null $url
     * @return array
     * @throws Exception
     */
    private function getAudio(?string $url): array
    {
        if (static::$isTesting) {
            return [UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'), 'test.mp3'];
        }

        if (! is_null($url)) {
            $name = uniqid();
            $file = '/tmp/'.$name;
            file_put_contents($file, Http::timeout(30)->get($url)->body());
        } else {
            $path = config('messenger-faker.paths.audio');
            $audio = File::files($path);

            if (! count($audio)) {
                $this->throwFailedException("No audio found within $path");
            }

            $file = Arr::random($audio, 1)[0];
            $name = $file->getFilename();
        }

        return [new UploadedFile($file, $name), $file];
    }

    /**
     * @param string $file
     */
    private function unlinkFile(string $file): void
    {
        if (! static::$isTesting) {
            unlink($file);
        }
    }
}
