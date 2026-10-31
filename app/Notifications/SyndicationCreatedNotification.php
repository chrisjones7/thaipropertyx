<?php

namespace App\Notifications;

use App\Models\NotificationPreference;
use App\Models\Syndication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SyndicationCreatedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Syndication $syndication
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $emailEnabled = NotificationPreference::query()
            ->where('user_id', $notifiable->id)
            ->where('event_type', 'syndication_created')
            ->value('email_enabled');

        if ($emailEnabled === null || (bool) $emailEnabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $propertyTitle = $this->syndication->property?->title
            ?? 'Property #'.$this->syndication->property_id;

        $sourceAgency = $this->syndication->sourceAgency?->name
            ?? 'The originating agency';

        return (new MailMessage)
            ->subject('TPX New Syndication - '.$propertyTitle)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A new property syndication has been created for your agency.')
            ->line('Property: '.$propertyTitle)
            ->line('Originating agency: '.$sourceAgency)
            ->line('You can review your shared properties in your TPX account.')
            ->action('View Notifications', route('agency.notifications'))
            ->line('You can manage notification emails in your TPX notification preferences.');
    }

    public function toArray(object $notifiable): array
    {
        $propertyTitle = $this->syndication->property?->title
            ?? 'Property #'.$this->syndication->property_id;

        $sourceAgency = $this->syndication->sourceAgency?->name
            ?? 'The originating agency';

        return [
            'event_type' => 'syndication_created',
            'title' => 'New Syndication',
            'message' => 'A new syndication for "'.$propertyTitle.'" has been created from '.$sourceAgency.'.',
            'property_id' => $this->syndication->property_id,
            'property_title' => $propertyTitle,
            'syndication_id' => $this->syndication->id,
            'source_agency_id' => $this->syndication->source_agency_id,
            'source_agency_name' => $sourceAgency,
            'target_agency_id' => $this->syndication->target_agency_id,
        ];
    }
}
