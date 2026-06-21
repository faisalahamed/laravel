<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;

class EmployeeController extends Controller
{
    public function index(Request $request)
    {
        $userId = $request->user()->id;
        return response()->json(Employee::where('user_id', $userId)->get());
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'uuid' => 'nullable|uuid|unique:employees,uuid',
            'name' => 'required|string|max:255',
            'phone' => 'nullable|string',
            'designation' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'status' => 'nullable|string',
        ]);

        $userId = $request->user()->id;
        $validated['user_id'] = $userId;

        $employee = Employee::create($validated);
        return response()->json($employee, 201);
    }

    public function show(Employee $employee)
    {
        return response()->json($employee);
    }

    public function update(Request $request, Employee $employee)
    {
        $validated = $request->validate([
            'uuid' => 'nullable|uuid|unique:employees,uuid,' . $employee->id,
            'name' => 'sometimes|required|string|max:255',
            'phone' => 'nullable|string',
            'designation' => 'nullable|string',
            'salary' => 'nullable|numeric',
            'status' => 'nullable|string',
        ]);

        $employee->update($validated);
        return response()->json($employee);
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();
        return response()->json(['message' => 'Employee deleted successfully']);
    }
}
