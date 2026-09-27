<?php

namespace App\Mail;

use App\Models\Project;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Generische Benachrichtigung für Workflow-Maßnahmen ("E-Mail senden"). Betreff
 * und Text sind bereits fertig gerendert (Projektvariablen ersetzt, siehe
 * App\Services\Workflow\ProjectVariables) – bewusst NICHT queued, da die App
 * keine Queue-Infrastruktur nutzt (QUEUE_CONNECTION=sync); der Aufrufer
 * (WorkflowActionRunner) fängt Fehler pro Empfänger einzeln ab.
 */
class WorkflowNotificationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $subjectLine,
        public string $bodyText,
        public Project $project,
    ) {
    }

    public function envelope(): Envelope
    {
        return new Envelope(subject: $this->subjectLine);
    }

    public function content(): Content
    {
        return new Content(
            view: 'mail.workflow-notification',
            with: ['bodyText' => $this->bodyText, 'project' => $this->project],
        );
    }
}
