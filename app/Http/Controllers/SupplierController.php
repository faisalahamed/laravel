<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = Supplier::orderBy('total_due', 'desc')
            ->where('user_id', $userId);

        if ($request->has('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        if ($request->has('filter')) {
            $filter = $request->get('filter');
            if ($filter === 'receivable' || $filter === 'payable') {
                $query->where('total_due', '>', 0.01);
            } elseif ($filter === 'advance') {
                $query->where('total_due', '<', -0.01);
            } elseif ($filter === 'settled') {
                $query->whereBetween('total_due', [-0.01, 0.01]);
            }
        } elseif ($request->has('has_due')) {
            $hasDue = $request->boolean('has_due');
            if ($hasDue) {
                $query->where('total_due', '>', 0.01);
            } else {
                $query->where('total_due', '<=', 0.01);
            }
        }

        // Calculate overall totals O(1) via SQL SUM
        $totalPayable = (double) Supplier::where('user_id', $userId)->where('total_due', '>', 0.01)->sum('total_due');
        $totalAdvance = abs((double) Supplier::where('user_id', $userId)->where('total_due', '<', -0.01)->sum('total_due'));

        if ($request->has('page')) {
            $perPage = $request->get('per_page', 50);
            $paginated = $query->paginate($perPage);

            return response()->json([
                'data' => $paginated->items(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'total_payable' => $totalPayable,
                'total_advance' => $totalAdvance,
            ]);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uuid' => 'nullable|uuid|unique:suppliers,uuid',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'total_due' => 'nullable|numeric',
            'status' => 'nullable|string',
        ]);

        $userId = $request->user()->id;
        $validated['user_id'] = $userId;

        $supplier = Supplier::create($validated);
        return response()->json($supplier, 201);
    }

    public function show(Supplier $supplier)
    {
        return response()->json($supplier);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'uuid' => 'nullable|uuid|unique:suppliers,uuid,' . $supplier->id,
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'total_due' => 'nullable|numeric',
            'status' => 'nullable|string',
        ]);

        $supplier->update($validated);
        return response()->json($supplier);
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return response()->json(['message' => 'Supplier deleted successfully']);
    }
}
