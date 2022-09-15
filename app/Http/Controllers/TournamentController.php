<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use Illuminate\Support\Facades\DB;
use App\Tournament;
use App\Court;
use App\Users_tournament;

use Illuminate\Database\QueryException;
use Exception;

class TournamentController extends Controller
{
    
    public function __construct(){
        $this->middleware( ['jwt.verify'] );//nuestro middleware personalizado
    }

    public function showTournament(Request $request){

        /************Si el id del torneo es 0, entonces con el id del usuario busco el último torneo en que participo*********/
        if( intval($request->id) === 0 )
            $request->id = DB::table('users_tournaments')->select(DB::raw('IFNULL(MAX(id_tournament),0) AS id'))->where('id_user',auth()->user()->id)->get()[0]->id;
        
        $tournament = DB::table('tournaments')
            ->select(['players_by_court','journals','initial_date','final_date','name'])
            ->where('id',$request->id)
        ->get();

        if(!isset($tournament[0])){
            $data = array(
                'times'=>[],
                'players'=>[],
                'number_players_by_court'=>0,
                'journals_total'=>0,
                'last_journal_close'=>0,
                'journal_active'=>0,
                'initial_date'=>null,
                'final_date'=>null
            );
          return response()->json($data,404);
        }

        $lastJournalClose = DB::table('courts')->select(['journal'])->where([['status','0'],['id_tournament',$request->id]])->orderBy('journal','desc')->first();
        
        $last_journal_close = 1;
        if(isset($lastJournalClose->journal)){
            if( $lastJournalClose->journal < $tournament[0]->journals)//mientras la ultima jornada cerrada sea menor al total de jornadas
                $last_journal_close = $lastJournalClose->journal + 1;
            else
                $last_journal_close = $lastJournalClose->journal;
        }
       
        $journal = 0;
        if( isset($request->journal) && $request->journal > 0 && $request->journal < $last_journal_close )
            $journal = $request->journal;
        else
            $journal = $last_journal_close;
     
        $times = DB::table('courts')->select(['date','time','number'])->where([['journal',$journal],['id_tournament',$request->id]])->orderBy('number','asc')->get();
        
        $players = DB::table('users_tournaments')->join('users','users.id','=','users_tournaments.id_user')
            ->select(['id_user','name','set1','set2','set3','position','court'])
            ->where([['id_tournament',$request->id],['journal',$journal]])
            ->orderBy('court','asc')
            // ->orderBy(DB::raw('set1+set2+set3'),'desc')
            ->orderBy('position','asc')
        ->get();

        foreach($players as $user)
            $user->img = 'http://localhost/diversos/apis/backend-cliente-almer/img/no-image.jpg';

        $data = array(
            'times'=>$times,
            'players'=>$players,
            'name'=>$tournament[0]->name,
            'number_players_by_court'=>$tournament[0]->players_by_court,
            'journals_total'=>$tournament[0]->journals,
            'last_journal_close'=>$last_journal_close,
            'journal_active'=>intval($journal),
            'initial_date'=>$tournament[0]->initial_date,
            'final_date'=>$tournament[0]->final_date
        );

      return response()->json($data,200);

    }

    public function addTournament(Request $request){

        $tournament = new Tournament();
        $tournament->name = $request->name;
        $tournament->players = count($request->players);
        $tournament->journals = count($request->journals);
        $tournament->initial_date = $request->initial_date;
        $tournament->final_date = $request->final_date;
        $tournament->courts = $request->courts;
        $tournament->players_by_court = $request->players_by_court;
        $tournament->save();
        $tournament->created = $tournament->created_at->format('Y-m-d H:i');
       
        $journal = 0;
        for($i=0;$i<$tournament->journals;$i++){
            $journal++;
            for($j=0;$j<$request->courts;$j++){
                $court = new Court();
                $court->number = $j+1;
                $court->date = $request->journals[$i][$j][0];
                $court->time = $request->journals[$i][$j][1];
                $court->id_tournament = $tournament->id;
                $court->journal = $journal;
                $court->save();
            }
        }

        $constant = $tournament->players / $request->players_by_court;
        $court = 1;
        
        for($i=0;$i<$tournament->journals;$i++){

            for($j=0;$j<$tournament->players;$j++){
                    $userTournament = new Users_tournament();
                    $userTournament->id_user = $request->players[$j]['id'];
                    $userTournament->journal = $i+1;
                    $userTournament->position = $request->players[$j]['position'];

                    if( ($j+1) % $request->players_by_court == 0)
                        $userTournament->court = $court++;
                    else
                        $userTournament->court = $court;

                    $userTournament->id_tournament = $tournament->id;
                    
                    $userTournament->save();

                    if($court > $constant)
                        $court = 1;
            }

        }
        
        return response()->json( $tournament->only(['id','name','players','created']), 201);

    }

    public function showAllTournaments(Request $request){
        $status = $request->has('status') ? $request->status : 1;
        $where = [['status',$status]];

        $listTournaments = DB::table('tournaments')->select(['name','id'])->where($where)->orderBy('created_at','desc')->get();

        if($request->has('id') && $request->id > 0)
           array_push($where,['tournaments.id',$request->id]);

        $tournaments = DB::table('tournaments')->select('*','id',DB::raw('( IFNULL((SELECT journal FROM courts WHERE status = 0 AND id_tournament = tournaments.id ORDER BY journal DESC LIMIT 0,1),0) ) as last_journal_close'))->where($where)->orderBy('created_at','desc')->get();
    
        $resp = array(
            'list'=>$listTournaments,
            'items'=>$tournaments
        );

        return response()->json($resp,200);
    }

    public function showTournamentByUser(Request $request){

         /************el id del Token*********/
        $tournaments = DB::table('users_tournaments')->join('tournaments','tournaments.id','=','users_tournaments.id_tournament')
            ->select(DB::raw('DISTINCT(id_tournament) AS id'),'name')
            ->where([['id_user',auth()->user()->id],['status','1']])
            ->orderBy('id','desc')
        ->get();

        return response()->json($tournaments,200);
    }

    public function updateTournament(Request $request){
       
        //Verificamos el total de jornadas y que el torneo no este archivado
        $totalJournals = DB::table('tournaments')->select('journals','status','players','courts','players_by_court')->where('id',$request->idTournament)->get()[0];

        if( $totalJournals->status > 1)
            return response()->json("No se puede actualizar los datos de un torneo archivado",400);

        if($request->journal > $totalJournals->journals)
            return response()->json("Total máximo de jornadas definidas: ".$totalJournals->journals,400);

        if( count($request->users) != $totalJournals->players)
            return response()->json("Total de usuarios definidos: ".$totalJournals->players,400);

        if( count($request->times) != $totalJournals->courts)
            return response()->json("Total de jornadas definidos: ".$totalJournals->courts,400);

        $constant = $totalJournals->players / $totalJournals->players_by_court;
        $court = 1;

        for($j=0;$j<$totalJournals->players;$j++){

            $affected = DB::table('users_tournaments')
                ->where([
                    ['id_tournament', $request->idTournament],
                    ['journal', $request->journal],
                    ['id_user',$request->users[$j]['id_user']]
                ])
                ->update([
                        'set1' => $request->users[$j]['set1'], 
                        'set2' => $request->users[$j]['set2'],
                        'set3' => $request->users[$j]['set3'],
                        'position' => $request->users[$j]['position'],
                        'court' => $court
                    ]
                );
            ($j+1) % $totalJournals->players_by_court === 0 ? $court++ : $court;//Lo posicionamos en la cancha que le corresponda

            if($court > $constant)
                $court = 1;
        }
       
        for($j=0;$j<$constant;$j++){
            $affected = DB::table('courts')
                ->where([
                    ['id_tournament', $request->idTournament],
                    ['journal', $request->journal],
                    ['number',$j+1]
                ])
                ->update(
                    ['date' => $request->times[$j][0] , 'time' => $request->times[$j][1]]
                );
        }
        
        return response()->json($request,200);
    }

    public function updateCloseJournalTournament(Request $request){

        //Verificamos el total de jornadas y que el torneo no este archivado
        $tournament = DB::table('tournaments')->select('journals','status','players','players_by_court')->where('id',$request->idTournament)->get()[0];

        if( $tournament->status > 1)
            return response()->json("No se puede cerrar la jornada de un torneo archivado",400);

        //Marcamos el status de los horarios de esas canchas como finalizados
        DB::table('courts')
        ->where([
            ['id_tournament', $request->idTournament],
            ['journal', $request->journal]
        ])
        ->update(
            ['status' => 0 ]
        );

        //Actualizamos las posiciones de los jugadores
        $users = DB::table('users_tournaments')->select('id_user')->where([
            ['id_tournament', $request->idTournament],
            ['journal', $request->journal]
        ])
        ->orderBy('court','asc')
        ->orderBy(DB::raw('set1+set2+set3'),'desc')
        ->orderBy('position','asc')
        ->get();

        if( $request->journal < $tournament->journals ){//Si se trata de la última jornada ya no actualizamos la jornada próxima
            
            $journalNext = $request->journal + 1;

            $position = 1;
            $constant = $tournament->players / $tournament->players_by_court;
            $court = 1;

            foreach($users as $user){
                DB::table('users_tournaments')
                ->where([
                    ['id_tournament', $request->idTournament],
                    ['journal', $journalNext],
                    ['id_user', $user->id_user]])
                ->update([
                    'position'=>$position,
                    'court'=>$court
                    ]);

                ($position) % $tournament->players_by_court === 0 ? $court++ : $court;//Lo posicionamos en la cancha que le corresponda
                $position++;
                if($court > $constant)
                    $court = 1;
            }
            
            // DB::update('UPDATE users_tournaments A1 
            // INNER JOIN users_tournaments A2 ON A1.id_user= A2.id_user 
            // AND A2.journal = ? AND A1.journal = ? AND A2.id_tournament = ?
            // SET A1.position = A2.position',
            // [$request->journal,$journalNext,$request->idTournament]);
        }
        return response()->json($request,200);
    }

    public function statusTournament(Request $request){
        $request['affected'] = DB::table('tournaments')->where('id',$request->id)->update(['status'=>$request->status]);
        return response()->json($request,200);
    }

    public function deleteTournament(Request $request){
        $delete = DB::table('tournaments')->where('id',$request->id)->delete();
        
        if($delete)
            $resp = [array('id'=>$request->id)];
        else
            $resp = [];

        return response()->json($resp,200);
    }

    public function tableGeneral(Request $request){

         /************Si el id del torneo es 0, entonces con el id del usuario busco el último torneo en que participo*********/
         if( intval($request->id) === 0 )
            $request->id = DB::table('users_tournaments')->select(DB::raw('IFNULL(MAX(id_tournament),0) AS id'))->where('id_user',auth()->user()->id)->get()[0]->id;

        $totalJournals = DB::table('tournaments')->select('journals','status')->where('id',$request->id)->get();

        if(!isset($totalJournals[0]))
            return response()->json([],404);

        $query = "";

        for($i=1;$i <= $totalJournals[0]->journals; $i++)
            $query .= "(SELECT (set1+set2+set3) FROM users_tournaments AS X WHERE id_tournament = $request->id AND journal = $i AND X.id_user = z.id_user) AS journal$i,";

        $users = DB::select("SELECT 
                        id_user,
                        U.name,
                        $query
                        (SELECT (SUM(set1)+SUM(set2)+SUM(set3)) AS total FROM  users_tournaments AS X WHERE id_tournament = $request->id AND X.id_user = Z.id_user) AS pointsGenerals,
                        (SELECT position FROM users_tournaments AS X WHERE journal = (SELECT IFNULL( (SELECT journal FROM courts WHERE status = 0 AND id_tournament = $request->id ORDER BY journal DESC LIMIT 0,1) , 1)) AND id_tournament = $request->id AND X.id_user = Z.id_user) AS position,
                        (SELECT court FROM users_tournaments AS X WHERE journal = (SELECT IFNULL( (SELECT journal FROM courts WHERE status = 0 AND id_tournament = $request->id ORDER BY journal DESC LIMIT 0,1) , 1)) AND id_tournament = $request->id AND X.id_user = Z.id_user) AS court
                    FROM users_tournaments AS Z 
                    INNER JOIN users AS U ON Z.id_user = U.id
                    WHERE id_tournament = $request->id GROUP BY id_user ORDER BY pointsGenerals DESC,court ASC");

        foreach($users as $user)
            $user->img = 'http://localhost/diversos/apis/backend-cliente-almer/img/no-image.jpg';

        $data = array(
            'users'=>$users,
            'journals'=>$totalJournals[0]->journals
        );

        return response()->json($data,200);
    }

    public function tableGeneralByUser(Request $request){

             /************Si el id del torneo es 0, entonces con el id del usuario busco el último torneo en que participo*********/
        $existe = 0;
        if( intval($request->id) === 0 )
            $request->id = DB::table('users_tournaments')->select(DB::raw('IFNULL(MAX(id_tournament),0) AS id'))->where('id_user',auth()->user()->id)->get()[0]->id;
        else
            $existe = DB::table('tournaments')->select(DB::raw("COUNT(id) AS existe"))->where('id',$request->id)->get()[0]->existe;
            
        if( intval($request->id) === 0 || $existe === 0 )
            return response()->json([],404);
        
        $ultimaJornadaCerrada = 
        DB::select("
            select ( IFNULL((SELECT journal FROM courts WHERE status = 0 AND id_tournament = $request->id ORDER BY journal DESC LIMIT 0,1),0) ) as last_journal_close
        ")[0]->last_journal_close;

        $totalJournals = DB::table('tournaments')->select('journals')->where('id',$request->id)->get()[0];

        $positionForJournal = [];
        DB::beginTransaction();
        try{
            for($i=1;$i <= $ultimaJornadaCerrada; $i++){

                DB::select('SET @num=0');
                $resp = DB::select("
                    SELECT pos FROM (
                        SELECT
                        id_user,
                        set1+set2+set3 AS TOTAL,
                        @num:=@num+1 AS pos
                        FROM users_tournaments
                        WHERE id_tournament = $request->id AND journal = $i ORDER BY total DESC
                    ) AS temporal WHERE temporal.id_user = ".auth()->user()->id."
                ")[0];

                array_push($positionForJournal,$resp->pos);
            }
             //DB::commit();
        }
        catch(QueryException $e){
            $resp=$e;
            //DB::rollBack();
        }
        catch(Exception $e){
            $resp=$e->getMessage();
            //DB::rollBack();
        }

        $resp = DB::table('users_tournaments')
            ->select('journal','set1','set2','set3',DB::raw('set1+set2+set3 AS pointsGeneral'),'court')
            ->where([['id_tournament',$request->id],['id_user',auth()->user()->id],['journal','<=',$ultimaJornadaCerrada]])->get();

        foreach($resp as $index => $property)
            $property->positionGeneral = $positionForJournal[$index];

        if($ultimaJornadaCerrada < $totalJournals->journals)
            $ultimaJornadaCerrada += 1;
       
        $nextGame = DB::table('courts')->select('date','time')->where([['id_tournament',$request->id],['journal',$ultimaJornadaCerrada]])->get()[0];
        $curt = DB::table('users_tournaments')->select('court')->where([['id_tournament',$request->id],['journal',$ultimaJornadaCerrada],['id_user',auth()->user()->id]])->get()[0]->court;
        
        $nextGame->curt = $curt;

        $data = array(
            'nextGame'=>$nextGame,
            'table'=>$resp
        );

        return response()->json($data,200);
    }

}
