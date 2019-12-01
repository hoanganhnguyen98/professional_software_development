<div class="dropdown">
    <li class="btn-group dropdown-toggle">
        <a href="#" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
            @if($table2->status == 'ready')
            <img src="{{ asset('img/table-short.jpg') }}" class="rounded-circle border border-success"> 
            @elseif($table2->status == 'prepare')
            <img src="{{ asset('img/table-short.jpg') }}" class="rounded-circle border border-primary"> 
            @elseif($table2->status == 'run')
            <img src="{{ asset('img/table-short.jpg') }}" class="rounded-circle border border-danger"> 
            @endif
        </a>
        <div class="dropdown-menu">
            <p class="dropdown-item text-info font-weight-bold">
                <i class="fas fa-map-marker-alt mr-5"></i>{{ $table2->table_id }}
            </p>
            <p class="dropdown-item text-info font-weight-bold">
                <i class="fas fa-chair mr-5"></i>{{ $table2->size }}
            </p>
            
            @if($table2->status == 'ready')
            <p class="dropdown-item text-success font-weight-bold">
                <i class="fas fa-comment-alt mr-5"></i>{{ trans('messages.status.'.$table2->status) }}
            </p>
            @elseif($table2->status == 'prepare')
            <p class="dropdown-item text-primary font-weight-bold">
                <i class="fas fa-comment-alt mr-5"></i>{{ trans('messages.status.'.$table2->status) }}
            </p>
            @elseif($table2->status == 'run')
            <p class="dropdown-item text-danger font-weight-bold">
                <i class="fas fa-comment-alt mr-5"></i>{{ trans('messages.status.'.$table2->status) }}
            </p>
            @endif
            @if($table2->status == 'ready')
            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-danger font-weight-bold" href="create-bill-{{ $table2->table_id }}">
                <i class="fas fa-file-signature mr-2"></i>{{ trans('messages.home.receptionist.create') }}
            </a>
            @elseif($table2->status == 'run')
            <div class="dropdown-divider"></div>
            <a class="dropdown-item text-primary font-weight-bold" href="#">
                <i class="fas fa-file-medical mr-2"></i>{{ trans('messages.home.receptionist.edit') }}
            </a>
            <a class="dropdown-item text-success font-weight-bold" href="#">
                <i class="fas fa-hand-holding-usd mr-2"></i>{{ trans('messages.home.receptionist.pay') }}
            </a>
            @endif
        </div> 
    </li>
</div>
