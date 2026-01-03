@extends(backpack_view('blank'))

@section('header')
    <div class="container-fluid">
        <h2 class="mb-0">AI провайдеры</h2>
        <small class="text-muted">Текущее состояние драйверов и ручной сброс.</small>
    </div>
@endsection

@section('content')
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-body table-responsive">
                    <table class="table table-striped mb-0">
                        <thead>
                            <tr>
                                <th>Драйвер</th>
                                <th>Статус</th>
                                <th>Сообщение</th>
                                <th>Блокировка до</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($providers as $provider)
                                <tr>
                                    <td>{{ $provider['name'] }} <span class="text-muted">({{ $provider['key'] }})</span></td>
                                    <td><span class="badge badge-{{ $provider['status'] === 'available' ? 'success' : 'warning' }}">{{ $provider['status'] }}</span></td>
                                    <td>{{ $provider['message'] ?? '—' }}</td>
                                    <td>{{ $provider['blocked_until'] ?? '—' }}</td>
                                    <td class="text-right">
                                        <form method="POST" action="{{ route('ai-content-generator.providers.reset', ['driver' => $provider['key']]) }}">
                                            @csrf
                                            <button class="btn btn-sm btn-outline-secondary" type="submit">Сбросить статус</button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
