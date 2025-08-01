@props(['items'])

<div class="row mb-4">
    <div class="col-12">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb bg-transparent p-0 mb-0">
                <li class="breadcrumb-item">
                    <a href="{{ route('dashboard') }}" class="text-primary text-decoration-none">Juntter</a>
                </li>
                
                @if(!empty($items))
                    <li class="breadcrumb-item">
                        <a href="{{ route('cobranca.index') }}" class="text-primary text-decoration-none">Cobrança</a>
                    </li>
                    @foreach($items as $item)
                        <li class="breadcrumb-item active" aria-current="page">{{ $item['label'] }}</li>
                    @endforeach
                @endif
            </ol>
        </nav>
    </div>
</div> 