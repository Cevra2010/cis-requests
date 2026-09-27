<div style="font-family: -apple-system, Helvetica, Arial, sans-serif; font-size: 14px; line-height: 1.6; color: #1f2937; max-width: 560px; margin: 0 auto; padding: 24px;">
    <p style="white-space: pre-line; margin: 0 0 20px;">{{ $bodyText }}</p>

    <p style="margin: 24px 0 0;">
        <a href="{{ route('project.show', $project->cis_row_id) }}"
           style="display:inline-block;padding:10px 18px;background:#4f46e5;color:#ffffff;text-decoration:none;border-radius:8px;font-weight:500;">
            Projekt öffnen
        </a>
    </p>

    <p style="margin-top:32px;padding-top:16px;border-top:1px solid #e5e7eb;font-size:12px;color:#9ca3af;">
        Automatische Benachrichtigung aus dem Projekt-Workflow von {{ $project->name }}.
    </p>
</div>
