<?php

namespace App\Http\Controllers;

use App\Models\BoardList;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class BoardListController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'title'      => 'required|string|max:200',
            'project_id' => 'required|integer',
            'is_done'    => 'nullable|boolean',
        ]);

        $data['user_id'] = auth()->id() ?? 1;
        $data['order'] = BoardList::where('project_id', $data['project_id'])->count();

        $list = BoardList::create($data);
        $list->tasks = [];

        return response()->json($list, 201);
    }

    public function update(Request $request, BoardList $boardList): JsonResponse
    {
        $data = $request->validate([
            'title'   => 'sometimes|string|max:200',
            'is_done' => 'nullable|boolean',
            'order'   => 'nullable|integer',
        ]);

        $boardList->update($data);
        return response()->json($boardList);
    }

    public function updateOrder(Request $request): JsonResponse
    {
        $items = $request->validate(['items' => 'required|array']);
        foreach ($items['items'] as $item) {
            BoardList::where('id', $item['id'])->update(['order' => $item['order']]);
        }
        return response()->json(['message' => 'Order updated']);
    }

    public function archive(BoardList $boardList): JsonResponse
    {
        $boardList->update(['is_archive' => 1]);
        return response()->json(['message' => 'Archived']);
    }
}
