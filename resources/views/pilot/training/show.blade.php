@extends('layouts.app')

@section('title', 'Pilot Training')
@section('title-flex')
@endsection
@section('content')
<div class="row">
    <div class="col-xl-3 col-md-12 col-sm-12 mb-12">
        <div class="card shadow mb-2">
            <div class="card-header bg-primary py-3 d-flex flex-row column-gap-3 pe-0">
                <h6 class="m-0 fw-bold text-white">
                    <i class="m-0 fw-bold text-white flex-grow-1">
                        <i class="fas fa-flag"></i>&nbsp;{{ $training->user->first_name }}'s training for
                        @foreach ($training->pilotRatings as $pilotRating)
                            {{$pilotRating->name}}
                        @endforeach
                    </i>
                </h6>
            </div>
            <div class="card-body">
                <dl class="copyable">
                    <dt>State</dt>
                    <dd><i class="{{ $statuses[$training->status]["icon"] }} text-{{ $statuses[$training->status]["color"] }}"></i>&ensp;{{ $statuses[$training->status]["text"] }}{{ isset($training->paused_at) ? ' (PAUSED)' : '' }}</dd>

                    <dt>Level</dt>
                    <dd>
                        {{ $training->pilotRatings[0]->name }}
                    </dd>

                    <dt>Callsign</dt>
                    <dd class="separator pb-3">
                        {{ $training->callsign->callsign }}
                    </dd>

                    <dt class="pt-2">Vatsim ID</dt>
                    <dd>
                        <a href="{{ route('user.show', $training->user->id) }}">
                            {{ $training->user->id }}
                        </a>
                        <button type="button" onclick="navigator.clipboard.writeText('{{ $training->user->id }}')"><i class="fas fa-copy"></i></button>
                        <a href="https://stats.vatsim.net/stats/{{ $training->user->id }}" target="_blank" title="VATSIM Stats" class="link-btn me-1"><i class="fas fa-chart-simple"></i></button></a>

                    </dd>
                    <dt> Name </dt>
                    <dd class="separator pb-3"><a href="{{ route('user.show', $training->user->id) }}"> {{ $training->user->name }}</a><button type="button" onclick="navigator.clipbaoard.writeText('{{ $training->user->first_name.' '.$training->user->last_name}}')"><i class="fas fa-copy"></i></button> </dd>

                    <dt class="pt-2">Instructor</dt>
                    <dd class="separator pb-3">{{ !empty($training->getInlineInstructors()) ? $training->getInlineInstructors() : '-' }}</dd>

                    <dt class="pt-2">Period</dt>
                    <dd>
                        @if ($training->started_at == null && $training->closed_at == null)
                            Training not started
                        @elseif ($training->closed_at == null)
                            {{ $training->started_at->toEuropeanDate() }} -
                        @elseif ($training->started_at != null)
                            {{ $training->started_at->toEuropeanDate() }} - {{ $training->closed_at->toEuropeanDate() }}
                        @else
                            N/A
                        @endif
                    </dd>

                    <dt>Applied</dt>
                    <dd>{{ $training->created_at->toEuropeanDate() }}</dd>

                    <dt>Closed</dt>
                    <dd>
                        @if ($training->closed_at != null)
                            {{ $training->closed_at->toEuropeanDate() }}
                        @else
                            -
                        @endif
                    </dd>
                </dl>

                @can('edit', [\App\Models\PilotTraining::class, $training])

                    <a href="{{ route('pilot.training.edit', $training->id)}}" class="btn btn-outline-primary btn-icon"><i class="fas fa-pencil"></i>&nbsp;Edit training</a>
                @endcan
            </div>
        </div>

    @can('update', $training)
        <div class="card shadow mb-4">

            <div class="card-body">
                <form action="#" method="POST">
                    @method('PATCH')
                    @csrf

                    <div class="mb-3">
                        <label class="form-label" for="trainingStateSelect">Select training state</label>
                        <select class="form-select"name="status" id="trainingStateSelect" @if(!Auth::user()->isModeratorOrAbove()) disabled @endif>
                            @foreach ($statuses as $id => $data)
                                @if($data["assignableByStaff"])
                                    @if($id == $training->status)
                                        <option value="{{ $id }}" selected>{{ $data["text"] }}</option>
                                    @else
                                        <option value="{{ $id }}">{{ $data["text"] }}</option>
                                    @endif
                                @endif
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3" id="closedReasonInput" style="display: none">
                        <label class="form-label" for="trainingCloseReason">Closed reason</label>
                        <input type="text" id="trainingCloseReason" class="form-control" name="closed_reason" placeholder="{{ $training->closed_reason }}" maxlength="65">
                    </div>

                    <div class="form-check">
                        <input class="form-check-input" type="checkbox" id="check1" name="paused_at" {{ $training->paused_at ? "checked" : "" }} @if(!Auth::user()->isModeratorOrAbove()) disabled @endif>
                        <label class="form-check-label" for="check1">
                            Paused
                            @if(isset($training->paused_at))
                                <span class='badge bg-danger'>{{ \Carbon\Carbon::create($training->paused_at)->diffForHumans(['parts' => 2]) }}</span>
                            @endif
                        </label>
                    </div>

                    <hr>
                    @if (\Auth::user()->isModeratorOrAbove())
                        <div class="mb-3">
                            <label class="form-label" for="assignInstructors">Assigned instructors: <span class="badge bg-secondary">Ctrl/Cmd+Click</span> to select multiple</label>
                            <select multiple class="form-select" name="instructors[]" id="assignInstructors">
                                @foreach($instructors as $instructor)
                                    <option value="{{ $instructor->id }}" {{ ($training->instructors->contains($instructor->id)) ? "selected" : "" }}>{{ $instructor->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif

                    <button type="submit" class="btn btn-primary">Save</button>

                </form>
            </div>
        </div>
    @endcan
    </div>

    <div class="col-xl-4 col-md-6 col-sm-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Timeline
                </h6>
            </div>
            @cannot('comment', [\App\Models\PilotTraining::class, \App\Models\Training::find($training->id)])
                <form action="{{ route('pilot.training.activity.comment') }}" method="POST">
                    @csrf
                    <div class="input-group">
                        <input type="hidden" name="pilot_training_id" value="{{ $training->id }}">
                        <input type="hidden" name="update_id" id="activity_update_id" value="">
                        <input type="text" name="comment" id="activity_comment" class="form-control border" placeholder="Your internal comment ..." maxlength="512">
                        <button class="btn btn-outline-primary" id="activity_button" type="submit">Comment</button>
                    </div>
                </form>                    
            @endcannot
            <div class="timeline">
                <ul class="sessions">
                    @foreach($activities as $activity)
                        @can('view', [\App\Models\TrainingActivity::class, \App\Models\Training::find($training->id), $activity->type])
                            <li data-id="{{ $activity->id }}">
                                <div class="time">
                                    @if($activity->type == "STATUS" || $activity->type == "TYPE")
                                        <i class="fas fa-right-left"></i>
                                    @elseif($activity->type == "MENTOR")
                                        @if($activity->new_data)
                                            <i class="fas fa-user-plus"></i>
                                        @elseif($activity->old_data)
                                            <i class="fas fa-user-minus"></i>
                                        @endif
                                    @elseif($activity->type == "PAUSE")
                                        <i class="fas fa-circle-pause"></i>
                                    @elseif($activity->type == "ENDORSEMENT")
                                        <i class="fas fa-check-square"></i>
                                    @elseif($activity->type == "COMMENT")
                                        <i class="fas fa-comment"></i>
                                    @endif

                                    @isset($activity->triggered_by_id)
                                        {{ \App\Models\User::find($activity->triggered_by_id)->name }} —
                                    @endisset

                                    {{ $activity->created_at->toEuropeanDateTime() }}
                                    @can('comment', [\App\Models\PilotTrainingActivity::class, \App\Models\PilotTraining::find($training->id)])
                                        @if($activity->type == "COMMENT" && now() <= $activity->created_at->addDays(1))
                                            <button class="btn btn-sm float-end" onclick="updateComment({{ $activity->id }}, '{{ $activity->comment }}')"><i class="fas fa-pencil"></i></button>
                                        @endif
                                    @endcan
                                </div>
                                <p>

                                    @if($activity->type == "STATUS")
                                        @if(($activity->new_data == -2 || $activity->new_data == -4) && isset($activity->comment))
                                            Status changed from <span class="badge text-bg-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->old_data]["text"] }}</span>
                                        to <span class="badge text-bg-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->new_data]["text"] }}</span>
                                        with reason <span class="badge text-bg-light">{{ $activity->comment }}</span>
                                        @else
                                            Status changed from <span class="badge text-bg-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->old_data]["text"] }}</span>
                                        to <span class="badge text-bg-light">{{ \App\Http\Controllers\TrainingController::$statuses[$activity->new_data]["text"] }}</span>
                                        @endif
                                    @elseif($activity->type == "TYPE")
                                        Training type changed from <span class="badge text-bg-light">{{ \App\Http\Controllers\TrainingController::$types[$activity->old_data]["text"] }}</span>
                                        to <span class="badge text-bg-light">{{ \App\Http\Controllers\TrainingController::$types[$activity->new_data]["text"] }}</span>
                                    @elseif($activity->type == "INSTRUCTOR")
                                        @if($activity->new_data)
                                            <span class="badge text-bg-light">{{ \App\Models\User::find($activity->new_data)->name }}</span> assigned as instructor
                                        @elseif($activity->old_data)
                                        <span class="badge text-bg-light">{{ \App\Models\User::find($activity->old_data)->name }}</span> removed as instructor
                                        @endif
                                    @elseif($activity->type == "PAUSE")
                                        @if($activity->new_data)
                                            Training paused
                                        @else
                                            Training unpaused
                                        @endif
                                    @elseif($activity->type == "ENDORSEMENT")
                                        @if(\App\Models\Endorsement::find($activity->new_data) !== null)
                                            @empty($activity->comment)
                                                <span class="badge text-bg-light">
                                                    {{ str(\App\Models\Endorsement::find($activity->new_data)->type)->lower()->ucfirst() }} endorsement
                                                </span> granted, valid to
                                                <span class="badge text-bg-light">
                                                    @isset(\App\Models\Endorsement::find($activity->new_data)->valid_to)
                                                        {{ \App\Models\Endorsement::find($activity->new_data)->valid_to->toEuropeanDateTime() }}
                                                    @else
                                                        Forever
                                                    @endisset
                                                </span>
                                            @else
                                                <span class="badge text-bg-light">
                                                    {{ str(\App\Models\Endorsement::find($activity->new_data)->type)->lower()->ucfirst() }} endorsement
                                                </span> granted, valid to
                                                <span class="badge text-bg-light">
                                                    @isset(\App\Models\Endorsement::find($activity->new_data)->valid_to)
                                                        {{ \App\Models\Endorsement::find($activity->new_data)->valid_to->toEuropeanDateTime() }}
                                                    @else
                                                        Forever
                                                    @endisset
                                                </span>
                                                for positions:
                                                @foreach(explode(',', $activity->comment) as $p)
                                                    <span class="badge text-bg-light">{{ $p }}</span>
                                                @endforeach
                                            @endempty
                                        @endif
                                    @elseif($activity->type == "COMMENT")
                                        {!! nl2br($activity->comment) !!}

                                        @if($activity->created_at != $activity->updated_at)
                                            <span class="text-muted">(edited)</span>
                                        @endif
                                    @endif

                                </p>
                            </li>
                        @endcan
                    @endforeach
                    <li>
                        <div class="time">
                            <i class="fas fa-flag"></i>
                            @isset($training->created_by)
                                {{ \App\Models\User::find($training->created_by)->name }} — 
                            @endisset
                            {{ $training->created_at->toEuropeanDateTime() }}
                        </div>
                        <p>
                            Training created
                        </p>
                    </li>
                </ul>
            </div>
        </div>
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 fw-bold text-white">
                    Application
                </h6>
            </div>
            <div class="card-body p-0">
                <div class="card bg-light mb-3">
                    <div class="card-body">

                        @if($training->english_only_training)
                            <i class="fas fa-flag-usa"></i>&nbsp;&nbsp;Requesting training in English only<br>
                        @else
                            <i class="fas fa-flag"></i>&nbsp;&nbsp;Requesting training in local language or English<br>
                        @endif

                        @isset($training->experience)
                            <i class="fas fa-book"></i>&nbsp;&nbsp;{{ $experiences[$training->experience]["text"] }}
                        @endisset
                    </div>
                </div>
            </div>

            <div class="p-4">
                <p class="fw-bold text-primary">
                    <i class="fas fa-envelope-open-text"></i>&nbsp;Remarks
                </p>

                @if (empty($training->comment))
                    <p><i>Not provided</i></p>
                @else
                    <p>{{ $training->comment }}</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-xl-5 col-md-6 col-sm-12 mb-12">
        <div class="card shadow mb-4">
            <div class="card-header bg-primary py-3 d-flex flex-row align-items-center justify-content-between">
                @if($training->status >= \App\Helpers\TrainingStatus::PRE_TRAINING->value && $training->status <= \App\Helpers\TrainingStatus::AWAITING_EXAM->value)
                    <h6 class="m-0 fw-bold text-white">
                @else
                    <h6 class="m-0 mt-1 mb-2 fw-bold text-white">
                @endif
                    Training Reports
                </h6>

                @if ($training->status >= \App\Helpers\TrainingStatus::PRE_TRAINING->value && $training->status <= \App\Helpers\TrainingStatus::AWAITING_EXAM->value)
                    <div class="dropdown" style="display: inline;">
                        <button class="btn btn-light icon dropdown-toggle" type="button" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <i class="fas fa-plus"></i> Create
                        </button>
                    

                        <div class="dropdown-menu" aria-labelledby="dropdownMenuButton">
                            @can('create', [\App\Models\PilotTrainingReport::class, $training])
                                @if ($training->status >= \App\Helpers\TrainingStatus::PRE_TRAINING->value)
                                    <a class="dropdown-item" href="{{ route('pilot.training.report.create', ['training' => $training->id])}}"><i class="fas fa-file"></i> Training Report</a>
                                @endif
                            
                            @else
                                <a href="#" class="dropdown-item disabled"><i class="fas fa-lock"></i>&nbsp;Training Report</a>
                            @endcan
                        </div>
                    </div>  
                @endif

            </div>
            <div class="card-body p-0">
                @can('viewAny', [\App\Models\PilotTrainingReport::class, $training])
                    <div class="accordion" id="reportAccordion">
                        @if ($reports->count() == 0)
                            <div class="card-text text-primary p-3">
                                No training reports yet.
                            </div>
                        @else
                            
                            @foreach ($reports as $report) 
                                @if (is_a($report, '\App\Models\PilotTrainingReport'))
                                    @if (!$report->draft || $report->draft && \Auth::user()->isInstructorOrAbove())
                                        
                                        @php
                                            $uuid = "instance-".Ramsey\Uuid\Uuid::uuid4();
                                        @endphp

                                        <div class="card">
                                            <div class="card-header p-0">
                                                <h5 class="mb-0">
                                                    <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $uuid}}" aria-expanded="true">
                                                        <i class="fas fa-fw fa-chevron-right me-2"></i>{{ $report->report_date->toEuropeanDate() }}
                                                        @if ($report->draft)
                                                            <span class="badge bg-danger">Draft</span>
                                                        @endif
                                                    </button>
                                                </h5>
                                            </div>
                                            <div id="{{ $uuid }}" class="collapse" data-bs-parent="#reportAccordion">
                                                <div class="card-body">
                                                    <small class="text-muted">
                                                        <i class="fas fa-user-edit"></i> {{ isset(\App\Models\User::find($report->written_by_id)->name) ? \App\Models\User::find($report->written_by_id)->name : "Unknown"}}&emsp;
                                                        @if (isset($report->lesson_id))
                                                            <i class="fas fa-scroll"></i> {{ $report->lesson->name}}
                                                        @endif
                                                        @can('update', $report)
                                                            <a href="{{ route('pilot.training.report.edit', $report->id)}}" class="float-end"><i class="fa fa-pen-square"></i> Edit</a>
                                                        @endcan
                                                    </small>

                                                    <div class="mt-2" id="markdown-content">
                                                        @markdown($report->content)
                                                    </div>

                                                    @if (isset($report->contentimprove) && !empty($report->contentimprove))
                                                        <hr>
                                                        <p class="fw-bold text-primary">
                                                            <i class="fas fa-clipboard-list-check"></i>&nbsp;Areas to improve
                                                        </p>
                                                        <div class="markdown-improve">
                                                            @markdown($report->contentimprove)
                                                        </div>
                                                    @endif

                                                    @if($report->attachments->count() > 0)
                                                        <hr>
                                                        @foreach($report->attachments as $attachment)
                                                            <div>
                                                                <a href="{{ route('pilot.training.object.attachment.show', ['attachment' => $attachment]) }}" target="_blank">
                                                                    <i class="fa fa-file"></i>&nbsp;{{ $attachment->file->name }}
                                                                </a>
                                                            </div>
                                                        @endforeach
                                                    @endif
                                                </div>
                                            </div>
                                        </div>

                                    @endif
                                @else
                                    @php
                                        $uuid = "instance-".Ramsey\Uuid\Uuid::uuid4();
                                    @endphp

                                    <div class="card">
                                        <div class="card-header p-0">
                                            <h5 class="mb-0 bg-lightorange">
                                                <button class="btn btn-link" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $uuid }}" aria-expanded="true">
                                                    <i class="fas fa-fw fa-chevron-right me-2"></i>{{ $reportModel->examination_date->toEuropeanDate() }}
                                                </button>
                                            </h5>
                                        </div>

                                        <div id="{{ $uuid }}" class="collapse" data-bs-parent="#reportAccordion">
                                            <div class="card-body">

                                                <small class="text-muted">
                                                    @if(isset($reportModel->position))
                                                        <i class="fas fa-map-marker-alt"></i> {{ \App\Models\Position::find($reportModel->position_id)->callsign }}&emsp;
                                                    @endif
                                                    <i class="fas fa-user-edit"></i> {{ isset(\App\Models\User::find($reportModel->examiner_id)->name) ? \App\Models\User::find($reportModel->examiner_id)->name : "Unknown" }}
                                                    @can('delete', [\App\Models\TrainingExamination::class, $reportModel])
                                                        <a class="float-end" href="{{ route('training.examination.delete', $reportModel->id) }}" onclick="return confirm('Are you sure you want to delete this examination?')"><i class="fa fa-trash"></i> Delete</a>
                                                    @endcan
                                                </small>

                                                <div class="mt-2">
                                                    @if($reportModel->result == "PASSED")
                                                        <span class='badge bg-success'>PASSED</span>
                                                    @elseif($reportModel->result == "FAILED")
                                                        <span class='badge bg-danger'>FAILED</span>
                                                    @elseif($reportModel->result == "INCOMPLETE")
                                                        <span class='badge bg-primary'>INCOMPLETE</span>
                                                    @elseif($reportModel->result == "POSTPONED")
                                                        <span class='badge bg-warning'>POSTPONED</span>
                                                    @endif
                                                </div>

                                                @if($reportModel->attachments->count() > 0)
                                                    @foreach($reportModel->attachments as $attachment)
                                                        <div>
                                                            <a href="{{ route('training.object.attachment.show', ['attachment' => $attachment]) }}" target="_blank">
                                                                <i class="fa fa-file"></i>&nbsp;{{ $attachment->file->name }}
                                                            </a>
                                                        </div>
                                                    @endforeach
                                                @endif

                                            </div>
                                        </div>
                                    </div>   
                                    
                                @endif
                            @endforeach
                        @endif

                    </div>
                @else
                    <div class="card-text text-primary p-3">
                        You don't have access to see the training reports.
                    </div>
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
    <script>
        // Edit activity comment
        function updateComment(id, oldText){
            document.getElementById('activity_update_id').value = id
            document.getElementById('activity_comment').value = oldText
            document.getElementById('activity_button').innerHTML = 'Update'

            document.getElementById('activity_comment').style.backgroundColor = '#fff7bd'
            document.getElementById('activity_comment').style.transition = 'background-color 100ms linear'
            setTimeout(function(){
                document.getElementById('activity_comment').style.backgroundColor = '#ffffff'
            }, 750)
        }

    </script>

    <!-- Training report accordion -->
    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Add minus icon for collapse element which is open by default
            var showCollapses = document.querySelectorAll(".collapse.show");
            showCollapses.forEach(function(collapse) {
                var cardHeader = collapse.previousElementSibling;
                var icon = cardHeader.querySelector(".fas");
                if (icon) {
                    icon.classList.add("fa-chevron-down");
                    icon.classList.remove("fa-chevron-right");
                }
            });

            // Toggle plus minus icon on show hide of collapse element
            var collapses = document.querySelectorAll(".collapse");
            collapses.forEach(function(collapse) {
                collapse.addEventListener('show.bs.collapse', function() {
                    var cardHeader = collapse.previousElementSibling;
                    var icon = cardHeader.querySelector(".fas");
                    if (icon) {
                        icon.classList.remove("fa-chevron-right");
                        icon.classList.add("fa-chevron-down");
                    }
                });

                collapse.addEventListener('hide.bs.collapse', function() {
                    var cardHeader = collapse.previousElementSibling;
                    var icon = cardHeader.querySelector(".fas");
                    if (icon) {
                        icon.classList.remove("fa-chevron-down");
                        icon.classList.add("fa-chevron-right");
                    }
                });
            });

            // Closure reason input
            var trainingStateSelect = document.querySelector('#trainingStateSelect');
            if(trainingStateSelect){
                toggleClosureReasonField(document.querySelector('#trainingStateSelect').value);

                var trainingStateSelect = document.querySelector('#trainingStateSelect');
                if (trainingStateSelect) {
                    trainingStateSelect.addEventListener('change', function () {
                        toggleClosureReasonField(trainingStateSelect.value);
                    });
                }

                function toggleClosureReasonField(val) {
                    var closedReasonInput = document.querySelector('#closedReasonInput');
                    if (closedReasonInput) {
                        if (val == -2) {
                            closedReasonInput.style.display = 'block';
                        } else {
                            closedReasonInput.style.display = 'none';
                        }
                    }
                }
            }

            var markdownContentLinks = document.querySelectorAll("#markdown-content p a, #markdown-improve p a");
            markdownContentLinks.forEach(function(link) {
                link.setAttribute('target', '_blank');
            });
        });
    </script>
@endsection