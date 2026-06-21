<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        $query = Customer::orderBy('total_due', 'desc')
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
        $totalReceivable = (double) Customer::where('user_id', $userId)->where('total_due', '>', 0.01)->sum('total_due');
        $totalAdvance = abs((double) Customer::where('user_id', $userId)->where('total_due', '<', -0.01)->sum('total_due'));

        if ($request->has('page')) {
            $perPage = $request->get('per_page', 50);
            $paginated = $query->paginate($perPage);

            return response()->json([
                'data' => $paginated->items(),
                'current_page' => $paginated->currentPage(),
                'last_page' => $paginated->lastPage(),
                'total' => $paginated->total(),
                'total_receivable' => $totalReceivable,
                'total_advance' => $totalAdvance,
            ]);
        }

        return response()->json($query->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uuid' => 'nullable|uuid|unique:customers,uuid',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'total_due' => 'nullable|numeric',
            'status' => 'nullable|string',
        ]);

        $userId = $request->user()->id;
        $validated['user_id'] = $userId;

        $customer = Customer::create($validated);
        return response()->json($customer, 201);
    }

    public function show(Customer $customer)
    {
        return response()->json($customer);
    }

    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'uuid' => 'nullable|uuid|unique:customers,uuid,' . $customer->id,
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'total_due' => 'nullable|numeric',
            'status' => 'nullable|string',
        ]);

        $customer->update($validated);
        return response()->json($customer);
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();
        return response()->json(['message' => 'Customer deleted successfully']);
    }
}
