<?php

namespace App\Notifications;

use App\Models\NotificationPreference;
use App\Models\Syndication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SyndicationRemovedNotification extends Notification
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
            ->where('event_type', 'syndication_removed')
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
            ->subject('TPX Syndication Removed - '.$propertyTitle)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('A property syndication has been removed from your TPX agency.')
            ->line('Property: '.$propertyTitle)
            ->line('Originating agency: '.$sourceAgency)
            ->line('This property is no longer included in your active syndicated listings.')
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
            'event_type' => 'syndication_removed',
            'title' => 'Syndication Removed',
            'message' => 'The syndication for "'.$propertyTitle.'" from '.$sourceAgency.' has been removed.',
            'property_id' => $this->syndication->property_id,
            'property_title' => $propertyTitle,
            'syndication_id' => $this->syndication->id,
            'source_agency_id' => $this->syndication->source_agency_id,
            'source_agency_name' => $sourceAgency,
            'target_agency_id' => $this->syndication->target_agency_id,
        ];
    }
}
