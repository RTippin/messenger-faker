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
     * @param  bool  $local
     * @param  string|null  $url
     * @return array
     *
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
                throw new Exception("No images found within $path");
            }

            $file = Arr::random($images, 1)[0];
            $name = $file->getFilename();
        } else {
            $name = uniqid();
            $file = sys_get_temp_dir().DIRECTORY_SEPARATOR.$name;
            file_put_contents(
                $file,
                Http::timeout(30)->get($url ?: config('messenger-faker.default_image_url'))->body()
            );
        }

        return [new UploadedFile($file, $name), $file];
    }

    /**
     * @param  string|null  $url
     * @return array
     *
     * @throws Exception
     */
    private function getDocument(?string $url): array
    {
        if (static::$isTesting) {
            return [UploadedFile::fake()->create('test.pdf', 500, 'application/pdf'), 'test.pdf'];
        }

        if (! is_null($url)) {
            $name = uniqid();
            $file = sys_get_temp_dir().DIRECTORY_SEPARATOR.$name;
            file_put_contents($file, Http::timeout(30)->get($url)->body());
        } else {
            $path = config('messenger-faker.paths.documents');
            $documents = File::files($path);

            if (! count($documents)) {
                throw new Exception("No documents found within $path");
            }

            $file = Arr::random($documents, 1)[0];
            $name = $file->getFilename();
        }

        return [new UploadedFile($file, $name), $file];
    }

    /**
     * @param  string|null  $url
     * @return array
     *
     * @throws Exception
     */
    private function getAudio(?string $url): array
    {
        if (static::$isTesting) {
            return [UploadedFile::fake()->create('test.mp3', 500, 'audio/mpeg'), 'test.mp3'];
        }

        if (! is_null($url)) {
            $name = uniqid();
            $file = sys_get_temp_dir().DIRECTORY_SEPARATOR.$name;
            file_put_contents($file, Http::timeout(30)->get($url)->body());
        } else {
            $path = config('messenger-faker.paths.audio');
            $audio = File::files($path);

            if (! count($audio)) {
                throw new Exception("No audio found within $path");
            }

            $file = Arr::random($audio, 1)[0];
            $name = $file->getFilename();
        }

        return [new UploadedFile($file, $name), $file];
    }

    /**
     * @param  string|null  $url
     * @return array
     *
     * @throws Exception
     */
    private function getVideo(?string $url): array
    {
        if (static::$isTesting) {
            return [UploadedFile::fake()->create('test.mov', 500, 'video/quicktime'), 'test.mov'];
        }

        if (! is_null($url)) {
            $name = uniqid();
            $file = sys_get_temp_dir().DIRECTORY_SEPARATOR.$name;
            file_put_contents($file, Http::timeout(60)->get($url)->body());
        } else {
            $path = config('messenger-faker.paths.videos');
            $videos = File::files($path);

            if (! count($videos)) {
                throw new Exception("No videos found within $path");
            }

            $file = Arr::random($videos, 1)[0];
            $name = $file->getFilename();
        }

        return [new UploadedFile($file, $name), $file];
    }

    /**
     * @param  string  $file
     */
    private function unlinkFile(string $file): void
    {
        if (! static::$isTesting) {
            unlink($file);
        }
    }
}
