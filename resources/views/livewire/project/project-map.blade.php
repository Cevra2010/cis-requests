<div>
    @foreach($projects as $project)
        <div>
            <a href="{{ route("project.edit",$project->cis_row_id) }}">{{ $project->name }}</a>
        </div>
    @endforeach
</div>
