<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class FileController extends Controller
{


    public function processFile(Request $request)
    {

        $validator = \Validator::make($request->all(), [
            'file'  => 'required|file|mimetypes:text/plain'
        ]);
    
        if ($validator->fails()) {
            return $validator->errors();
        }

        $data = [];
        foreach (file($request->file) as $row) {
            try {
                $data[] = $this->rowData($row);
            } catch (\Throwable $th) {
                return response()->json([
                    'error'     => true,
                    'message'   => 'Arquivo inválido'
                ]);
            }
            
        }

        return response()->json($data,200);

    }

    public function rowData($row)
    {   

        $id             = substr($row, 0, 3);
        $date           = date("Y-m-d", strtotime(substr($row, 3, 8)));
        $amount         = number_format(substr($row, 11, 10)/100,2,".","");
        $customer       = [
            "name"      => trim(substr($row, 23, 20)),
            "address"   => $this->getAddress(substr($row, 43, 8))
        ];
        $installments   = $this->getInstallments(substr($row, 21, 2), $amount, $date);

        return compact("id","date","amount","customer","installments");
    }

    public function getAddress($postal_code)
    {  
        $client = new \GuzzleHttp\Client();

        $response = $client->request('GET', "https://viacep.com.br/ws/{$postal_code}/json");
        $content = json_decode($response->getBody(),true);

        return [
            "street"        => $content['logradouro'],
            "neighborhood"  => $content['bairro'],
            "city"          => $content['localidade'],
            "state"         => $content['uf'],
            "postal_code"   => $content['cep']
        ];
    }

    public function getInstallments($installment,$amount_original,$date)
    {   
        $data = [];
        $amount_total = 0;
        for ($i=1; $i <= $installment; $i++)
        {

            $amount = number_format($amount_original / $installment,2,".","");
            $date = $this->nextBusinessDay(date('Y-m-d', strtotime('+30 days', strtotime($date))));

            $data[] = [
                "installment"   => $i,
                "amount"        => $amount,
                "date"          => $date
            ];

            $amount_total += $amount;
        }

        //add amount diference
        $amount_total = number_format($amount_total,2,".","");
        $diff = $amount_original - $amount_total;
        $data[0]['amount'] = number_format($data[0]['amount'] + $diff,2,".","");
        

        return $data;
    }

    public function nextBusinessDay($date)
    {
        $dayweek = date('w', strtotime($date));
        if ($dayweek == "0") $add_day = 1;
        else if ($dayweek == "6") $add_day = 2;
        else $add_day = 0;
        return date("Y-m-d", strtotime("+{$add_day} days", strtotime($date)));
    }
}
