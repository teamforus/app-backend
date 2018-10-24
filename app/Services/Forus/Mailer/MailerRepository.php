<?php

namespace App\Services\Forus\Mailer;


use App\Services\Forus\Mailer\Models\MailJob;
use Illuminate\Mail\Message;

class MailerRepository
{
    private $storage_path = 'mail_bus/attachments/';
    private $storageDriver;

    public function __construct()
    {
        $this->storageDriver = config('mail.storage_driver');
    }

    public function push($view, $scope, $message, $attachments) {
        $attachments = $this->storeAttachments($attachments);
        $payload = compact('view', 'scope', 'message', 'attachments');

        return Models\MailJob::create([
            'state'     => 'pending',
            'payload'   => serialize($payload)
        ]);
    }

    protected function storeAttachments($attachments) {
        return collect($attachments)->map(function($attachment) {
            if (!isset($attachment[0]))
                return false;

            $path = $this->storage_path . app(
                'token_generator'
                )->generate(32);

            $this->storage()->put($path, $attachment[0]);
            $attachment[0] = $path;
            return $attachment;
        })->filter(function($attachment) {
            return $attachment;
        })->toArray();
    }

    /**
     * @param MailJob $mailJob
     */
    public function sendMail(MailJob $mailJob) {
        $mailJob->update(['state' => 'processing']);
        $payload = unserialize($mailJob->payload);

        $message = $payload['message'];
        $attachments = $payload['attachments'];

        app('mailer')->send(
            $payload['view'],
            $payload['scope'],
            function(Message $msg) use ($message, $attachments) {
            foreach ($message as $key => $value) {
                if (gettype($value) != 'array')
                    $value = [$value];

                call_user_func_array([$msg, $key], $value);
            }

            /*foreach($attachments as $attachment) {
                $attachment[0] = storage_path('app/' . $attachment[0]);
                call_user_func_array([$msg,'attach'], $attachment);
            }*/
        });

        $mailJob->update(['state' => 'success']);
    }

    /**
     * Delete attachments older than 1 day
     * @return void
     */
    public function clearOldAttachments() {
        $storage = $this->storage();
        $files = collect($storage->allFiles($this->storage_path));

        $files->each(function($file) use ($storage) {
            $createdAt = $storage->lastModified($file);

            if (strtotime('+1 day', $createdAt) < time()) {
                $storage->delete($file);
            }
        });
    }

    /**
     * Get storage
     * @return \Storage
     */
    private function storage() {
        return app()->make('filesystem')->disk($this->storageDriver);
    }
}