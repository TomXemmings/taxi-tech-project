<?php

namespace App\Http\Controllers;

use App\Events\DriverCreated;
use App\Models\Driver;
use Illuminate\Http\Request;

class DriverController extends Controller
{
    public function index(Request $request)
    {
        $query = Driver::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where('name', 'like', "%$search%")
                ->orWhere('surname', 'like', "%$search%")
                ->orWhere('phone', 'like', "%$search%");
        }

        $drivers = $query->paginate(10);

        if ($request->ajax()) {
            return response()->json($drivers);
        }

        return view('dashboard', compact('drivers'));
    }

    public function destroy($id)
    {
        $driver = Driver::find($id);

        if (!$driver) {
            return response()->json(['message' => 'Driver not found'], 404);
        }

        $driver->delete();

        return response()->json(['message' => 'Driver deleted successfully']);
    }

    public function store(Request $request)
    {
        $driver = Driver::create($request->all());
        event(new DriverCreated($driver));

        return response()->json(['message' => 'Водитель добавлен!']);
    }
}
