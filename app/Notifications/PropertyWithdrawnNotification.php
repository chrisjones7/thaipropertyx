<?php

namespace App\Notifications;

use App\Models\NotificationPreference;
use App\Models\Property;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PropertyWithdrawnNotification extends Notification
{
    use Queueable;

    public function __construct(
        public Property $property
    ) {}

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $emailEnabled = NotificationPreference::query()
            ->where('user_id', $notifiable->id)
            ->where('event_type', 'property_unavailable')
            ->value('email_enabled');

        if ($emailEnabled === null || (bool) $emailEnabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $propertyTitle = $this->property->title ?: 'Property unavailable';
        $ownerAgency = $this->property->agency?->name ?? 'The listing agency';

        return (new MailMessage)
            ->subject('TPX Property Withdrawn - '.$propertyTitle)
            ->greeting('Hello '.$notifiable->name.',')
            ->line('The property "'.$propertyTitle.'" has been withdrawn from the TPX exchange by '.$ownerAgency.'.')
            ->line('This property is no longer available for active syndication.')
            ->line('Please review your imported listings and take any necessary action.')
            ->action('View Notifications', route('agency.notifications'))
            ->line('You can manage notification emails in your TPX notification preferences.');
    }

    public function toArray(object $notifiable): array
    {
        $propertyTitle = $this->property->title ?: 'Property unavailable';
        $ownerAgency = $this->property->agency?->name ?? 'The listing agency';

        return [
            'event_type' => 'property_withdrawn',
            'title' => 'Property Withdrawn',
            'message' => 'The property "'.$propertyTitle.'" has been withdrawn from the TPX exchange by '.$ownerAgency.'.',
            'property_id' => $this->property->id,
            'property_title' => $propertyTitle,
            'source_agency_id' => $this->property->agency_id,
            'source_agency_name' => $ownerAgency,
        ];
    }
}
