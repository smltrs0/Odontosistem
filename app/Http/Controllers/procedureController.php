<?php

namespace App\Http\Controllers;


use App\procedure;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;

class procedureController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $procedimientos = procedure::paginate(15);
        return view('procedures.index', compact('procedimientos'));
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return \view('procedures.crear');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $request->validate([
            'title'=> 'required',
            'className'=>'required'
        ]);
         
        $procedimiento= new procedure([
            ''=> $request->title,
            
        ]);
        $procedimiento->save();
        return redirect('/procedures')->with('success', 'saved!');
        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit(procedure $procedure)
    {
        $procedimiento = $procedure;
        return \view('procedures.actualizar', \compact('procedimiento'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request,procedure $procedure)
    {
       $procedure->key_p = $request->key_p;
       $procedure->title = $request->title;
       $procedure->code= $request->code;
       $procedure->price = $request->price;
       $procedure->className = $request->className;
       $procedure->type = $request->type;
       $procedure->apply = $request->apply;
       $procedure->ClearBefore = $request->ClearBefore;
       $procedure->save();

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        procedure::destroy($id);
        return \redirect('procedures')->with('success', 'Se ha eliminado correctamente');
       
    }
}
