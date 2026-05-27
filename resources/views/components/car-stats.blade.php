@props(['carModel'])

<dl class="grid grid-cols-2 sm:grid-cols-3 gap-3 text-sm">
    <div class="bg-racing-700 rounded p-3 border border-racing-600">
        <dt class="text-gray-400">Power</dt>
        <dd class="text-white font-semibold">{{ $carModel->power }}</dd>
    </div>
    <div class="bg-racing-700 rounded p-3 border border-racing-600">
        <dt class="text-gray-400">Acceleration</dt>
        <dd class="text-white font-semibold">{{ $carModel->acceleration }}</dd>
    </div>
    <div class="bg-racing-700 rounded p-3 border border-racing-600">
        <dt class="text-gray-400">Weight</dt>
        <dd class="text-white font-semibold">{{ $carModel->weight }}</dd>
    </div>
    <div class="bg-racing-700 rounded p-3 border border-racing-600">
        <dt class="text-gray-400">Grip</dt>
        <dd class="text-white font-semibold">{{ $carModel->grip }}</dd>
    </div>
    <div class="bg-racing-700 rounded p-3 border border-racing-600">
        <dt class="text-gray-400">Handling</dt>
        <dd class="text-white font-semibold">{{ $carModel->handling }}</dd>
    </div>
    <div class="bg-racing-700 rounded p-3 border border-racing-600">
        <dt class="text-gray-400">Durability</dt>
        <dd class="text-white font-semibold">{{ $carModel->durability }}</dd>
    </div>
</dl>
