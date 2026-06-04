<?php

namespace App\Http\Controllers;

use App\Models\Part;
use App\Services\PartSellService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class PartSellController extends Controller
{
    public function __construct(
        private readonly PartSellService $partSellService,
    ) {}

    public function destroy(Request $request, Part $part): RedirectResponse
    {
        $this->authorize('delete', $part);

        $quote = $this->partSellService->sell($request->user(), $part);

        $line = $quote->lines[0];

        return redirect()
            ->route('garage.index')
            ->withFragment('parts')
            ->with('status', 'part-sold')
            ->with('sold_label', $line->label)
            ->with('sold_amount', $quote->total);
    }
}
