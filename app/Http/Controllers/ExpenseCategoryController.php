<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use Illuminate\Http\Request;

class ExpenseCategoryController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        return response()->json(ExpenseCategory::where('user_id', $userId)->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'user_id' => 'nullable|integer',
        ]);

        $userId = $request->user()->id;

        $category = ExpenseCategory::create([
            'user_id' => $userId,
            'name' => $validated['name'],
        ]);

        return response()->json($category, 201);
    }

    public function destroy(ExpenseCategory $expenseCategory)
    {
        $expenseCategory->delete();
        return response()->json(['message' => 'Category deleted successfully']);
    }
}
