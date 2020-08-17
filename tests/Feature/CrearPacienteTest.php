<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Tests\TestCase;

class CrearPacienteTest extends TestCase
{
    use DatabaseMigrations;
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testCrearPaciente()
    {
        $this->visit('/pacientes/create')
            ->type('samuel', 'name')
            ->type('Trias','last_name')
            ->type('24186725','dni')
            ->type('marhuante','address')
            ->type('2522', 'phone')
            ->type('smltrs0@gmail.com','email')
            ->press('crear-paciente');


        $response->assertStatus(200);
    }
}
