<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\CarpetQueues;
use App\Carpets;
use App\Customers;
use App\Washtypes;
use App\Workers;
use Carbon\Carbon;

class CarpetbookingController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $carpetqueuesList= DB::table('carpet_queues')
        ->join('customers', 'carpet_queues.customer_id', '=', 'customers.id')
        ->join('carpets', 'carpet_queues.carpet_id', '=', 'carpets.id')
        ->join('wash_type', 'carpet_queues.washtype_id', '=', 'wash_type.id')
        ->select('carpet_queues.id','carpet_queues.queue_number','carpet_queues.booking_date','carpet_queues.status','customers.name as customer','carpets.color_of_carpet','carpets.type_of_carpet','wash_type.wash_type')
        ->where('carpet_queues.booking_date', '>',Carbon::now('Asia/Jakarta')->toDateString())
        ->orderby('id','asc')->get();
        // dd($carpetqueuesList);
        return view('admin.carpetbookingView.index',compact('carpetqueuesList'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('admin.carpetbookingView.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validateData = $request->validate([
            'color_of_carpet' => 'required',
            'type_of_carpet' => 'required',
            'wash_type'=>'required',
            'customer_name' => 'required',
            'booking_date' => 'required',
        ]);

        $carpetqueueform=$request->except('_token','updated_at','created_at');
        $checkAntrian = CarpetQueues::where('booking_date', '=',$carpetqueueform['booking_date'])->count();
        $bookingDateCheck = Carbon::parse($carpetqueueform['booking_date'])->format('l');

        if($bookingDateCheck != 'Friday'){
            //ambil id customer dari database
            $customerData = Customers::select('id')
                ->where('name', '=', $carpetqueueform['customer_name'])->get()->toArray();

            //ambil id karpet dan id customer dari database
            $carpetData = Carpets::select('id','customer_id')
                ->where('color_of_carpet', '=', $carpetqueueform['color_of_carpet'])
                ->where('type_of_carpet', '=', $carpetqueueform['type_of_carpet'])
                ->where('customer_id', '=', $customerData[0]['id'])
                ->get()->toArray();
            
            //passing carpet_id dan customer_id ke array baru
            $carpetData = ["customer_id"=>$carpetData[0]['customer_id'],"carpet_id"=>$carpetData[0]['id']];
            //gabung array baru dengan array antrian
            $carpetqueueData = array_merge($carpetqueueform,$carpetData);
            
            //ambil data jenis cuci
            $washtypeData= Washtypes::select('id')
                ->where('wash_type', '=', $carpetqueueform['wash_type'])
                ->where('type_of_carpets', '=', $carpetqueueform['type_of_carpet'])
                ->get()->toArray();

            //passing carpet_id dan customer_id ke array baru
            $washtypeData = ["washtype_id"=>$washtypeData[0]['id'],'status'=>'dalam antrian'];
            //gabung array baru dengan array antrian
            $carpetqueueData = array_merge($carpetqueueData,$washtypeData);

            // //ambil data pegawai 1
            // $worker1Data = Workers::select('id')
            //     ->where('name', '=', $carpetqueueData['worker1_name'])
            //     ->get()->toArray();
            // $worker2Data = Workers::select('id')
            // ->where('name', '=', $carpetqueueData['worker2_name'])
            // ->get()->toArray();
            

            // //passing worker_id ke array baru   
            // $workerData = ["worker1_id"=>$worker1Data[0]['id'],"worker2_id"=>$worker2Data[0]['id']];

        
            // //gabung array baru dengan array antrian
            // $carpetqueueData = array_merge($carpetqueueData,$workerData);
            

            // //gabungkan estimasi waktu
            // if($carpetqueueData['estimation_time_date'] != null){
            //     $estimation_time = ['estimation_time'=>$carpetqueueData['estimation_time_date']];
            // }else{
            //     $estimation_time = ['estimation_time'=>Carbon::now()->addDays(3)];
            // }
            // $carpetqueueData = array_merge($carpetqueueData,$estimation_time);
            $sumQueue= CarpetQueues::where('booking_date', '=',$carpetqueueform['booking_date'])->count();
                        
            if($sumQueue > 0){
                $sumQueue= CarpetQueues::select('queue_number')->where('booking_date', '=',$carpetqueueform['booking_date'])->orderby('queue_number','desc')->first();
                $convertSum= $sumQueue->queue_number;
                $queueNumber = ['queue_number'=>$convertSum+1];
                $carpetqueueData = array_merge($carpetqueueData,$queueNumber);
            }else{
                $queueNumber = ['queue_number'=>1];
                $carpetqueueData = array_merge($carpetqueueData,$queueNumber);
            }

            //menghapus data yang tidak diperlukan
            unset($carpetqueueData['color_of_carpet'],$carpetqueueData['type_of_carpet'],$carpetqueueData['customer_name'],$carpetqueueData['wash_type']);
            // dd($carpetqueueData);

            $saved = CarpetQueues::create($carpetqueueData);
            return redirect()->route('carpetbooking.index');
        }else{
            return redirect()->route('carpetbooking.index')->with('status','Maaf, Kami libur Pada hari jumat ');
        }
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
    public function edit($id)
    {

        $carpetqueuesFind= DB::table('carpet_queues')
        ->join('customers', 'carpet_queues.customer_id', '=', 'customers.id')
        ->join('carpets', 'carpet_queues.carpet_id', '=', 'carpets.id')
        ->join('wash_type', 'carpet_queues.washtype_id', '=', 'wash_type.id')
        ->select('carpet_queues.id','carpet_queues.booking_date','customers.name as customer','carpets.color_of_carpet','carpets.type_of_carpet','wash_type.wash_type')
        ->where('carpet_queues.id','=',$id)->get();
        // $workerList= Workers::all();
        // dd($carpetqueuesFind);
        return view('admin.carpetbookingView.edit',compact('carpetqueuesFind'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $validateData = $request->validate([
            'color_of_carpet' => 'required',
            'type_of_carpet' => 'required',
            'wash_type'=>'required',
            'customer_name' => 'required',
            'booking_date' => 'required',
        ]);

        $carpetqueueform=$request->except('_token','updated_at','created_at');
        $checkAntrian = CarpetQueues::where('booking_date', '=',$carpetqueueform['booking_date'])->count();
        $bookingDateCheck = Carbon::parse($carpetqueueform['booking_date'])->format('l');

        if($bookingDateCheck != 'Friday'){
            //ambil id customer dari database
            $customerData = Customers::select('id')
                ->where('name', '=', $carpetqueueform['customer_name'])->get()->toArray();

            //ambil id karpet dan id customer dari database
            $carpetData = Carpets::select('id','customer_id')
                ->where('color_of_carpet', '=', $carpetqueueform['color_of_carpet'])
                ->where('type_of_carpet', '=', $carpetqueueform['type_of_carpet'])
                ->where('customer_id', '=', $customerData[0]['id'])
                ->get()->toArray();
            
            //passing carpet_id dan customer_id ke array baru
            $carpetData = ["customer_id"=>$carpetData[0]['customer_id'],"carpet_id"=>$carpetData[0]['id']];
            //gabung array baru dengan array antrian
            $carpetqueueData = array_merge($carpetqueueform,$carpetData);
            
            //ambil data jenis cuci
            $washtypeData= Washtypes::select('id')
                ->where('wash_type', '=', $carpetqueueform['wash_type'])
                ->where('type_of_carpets', '=', $carpetqueueform['type_of_carpet'])
                ->get()->toArray();

            //passing carpet_id dan customer_id ke array baru
            $washtypeData = ["washtype_id"=>$washtypeData[0]['id']];
            //gabung array baru dengan array antrian
            $carpetqueueData = array_merge($carpetqueueData,$washtypeData);

            // //ambil data pegawai 1
            // $worker1Data = Workers::select('id')
            //     ->where('name', '=', $carpetqueueData['worker1_name'])
            //     ->get()->toArray();
            // $worker2Data = Workers::select('id')
            // ->where('name', '=', $carpetqueueData['worker2_name'])
            // ->get()->toArray();
            

            // //passing worker_id ke array baru   
            // $workerData = ["worker1_id"=>$worker1Data[0]['id'],"worker2_id"=>$worker2Data[0]['id']];

        
            // //gabung array baru dengan array antrian
            // $carpetqueueData = array_merge($carpetqueueData,$workerData);
            

            // //gabungkan estimasi waktu
            // if($carpetqueueData['estimation_time_date'] != null){
            // $estimation_time = ['estimation_time'=>$carpetqueueData['estimation_time_date']];
            // }else{
            //     $estimation_time = ['estimation_time'=>Carbon::now()->addDays(3)];
            // }
            // $carpetqueueData = array_merge($carpetqueueData,$estimation_time);
            
            $sumQueue= CarpetQueues::where('booking_date', '=',$carpetqueueform['booking_date'])->count();
                        
            if($sumQueue > 0){
                $sumQueue= CarpetQueues::select('queue_number')->where('booking_date', '=',$carpetqueueform['booking_date'])->orderby('queue_number','desc')->first();
                $convertSum= $sumQueue->queue_number;
                $queueNumber = ['queue_number'=>$convertSum+1];
                $carpetqueueData = array_merge($carpetqueueData,$queueNumber);
            }else{
                $queueNumber = ['queue_number'=>1];
                $carpetqueueData = array_merge($carpetqueueData,$queueNumber);
            }

            //menghapus data yang tidak diperlukan
            unset($carpetqueueData['color_of_carpet'],$carpetqueueData['type_of_carpet'],$carpetqueueData['customer_name'],$carpetqueueData['wash_type']);
            // dd($carpetqueueData);

            $saved = CarpetQueues::whereId($id)->update($carpetqueueData);
            return redirect()->route('carpetbooking.index');
        }else{
            return redirect()->route('carpetbooking.index')->with('status','Maaf, Kami libur Pada hari jumat ');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        CarpetQueues::find($id)->delete();
        return redirect()->route('carpetbooking.index');
    }
}
