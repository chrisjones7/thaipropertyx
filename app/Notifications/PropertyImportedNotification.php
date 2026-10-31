<?php

namespace App\Notifications;

use App\Models\NotificationPreference;
use App\Models\Syndication;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PropertyImportedNotification extends Notification
{
    use Queueable;

    public function __construct(public Syndication $syndication)
    {
    }

    public function via(object $notifiable): array
    {
        $channels = ['database'];

        $emailEnabled = NotificationPreference::query()
            ->where('user_id', $notifiable->id)
            ->where('event_type', 'property_imported')
            ->value('email_enabled');

        if ($emailEnabled === null || (bool) $emailEnabled) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $property = $this->syndication->property;
        $receivingAgency = $this->syndication->targetAgency;

        $propertyTitle = $property?->title
            ?? 'Property #' . $this->syndication->property_id;

        $receivingAgencyName = $receivingAgency?->name
            ?? 'Unknown Agency';

        return (new MailMessage)
            ->subject('TPX Property Imported - ' . $propertyTitle)
            ->greeting('Hello ' . $notifiable->name . ',')
            ->line(
                'Your property, ' . $propertyTitle .
                ', has been imported by ' . $receivingAgencyName . '.'
            )
            ->line('The property is now syndicated to the receiving agency.')
            ->action('View Notifications', route('agency.notifications'))
            ->line(
                'You can manage notification emails in your TPX notification preferences.'
            );
    }

    public function toArray(object $notifiable): array
    {
        $propertyTitle = $this->syndication->property?->title;
        $receivingAgencyName = $this->syndication->targetAgency?->name;

        return [
            'event_type' => 'property_imported',

            'title' => 'Property Imported',

            'message' => $propertyTitle && $receivingAgencyName
                ? 'Your property, ' . $propertyTitle .
                  ', has been imported by ' . $receivingAgencyName . '.'
                : 'Your property has been imported by another agency.',

            'property_id' => $this->syndication->property_id,

            'property_title' => $propertyTitle,

            'syndication_id' => $this->syndication->id,

            'source_agency_id' => $this->syndication->source_agency_id,

            'target_agency_id' => $this->syndication->target_agency_id,

            'receiving_agency' => $receivingAgencyName,
        ];
    }
}
