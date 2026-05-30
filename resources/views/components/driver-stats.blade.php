@props(['stats', 'labels' => []])

<dl class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-sm">
    @foreach (['power', 'acceleration', 'grip', 'handling'] as $key)
        <div class="bg-racing-700 rounded p-3 border border-racing-600">
            <dt class="text-gray-400">{{ $labels[$key] ?? ucfirst($key) }}</dt>
            <dd class="text-white font-semibold">{{ $stats[$key] }}</dd>
        </div>
    @endforeach
</dl>
