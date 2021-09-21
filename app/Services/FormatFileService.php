<?php

namespace App\Services;

use Carbon\Carbon;
use GuzzleHttp\Client;

class FormatFileService
{
    private $content = [];
    private $formated = [];
    private $result = [];

    public function setContent($content)
    {
        $this->content = $content;    
    }

    public function read()
    {
        $this->formated = [
            'id'            => substr($this->content, 0, 3),
            'date'          => substr($this->content, 3, 8),
            'amount'        => substr($this->content, 11, 10),
            'installments'  => substr($this->content, 21, 2),
            'customer'      => substr($this->content, 23, 20),
            'postal_code'   => substr($this->content, 43, 8)
        ];

        return $this;
    }
    
    public function format()
    {
        $amount = str_split($this->formated['amount'], strlen($this->formated['amount']) - 2);
        $amount = (float) $amount[0] . '.'  . $amount[1];

        $this->formated = [
            'id'            => $this->formated['id'],
            'date'          => date('Y-m-d', strtotime($this->formated['date'])),
            'amount'        => number_format($amount, 2, '.', ''),
            'installments'  => (int) $this->formated['installments'],
            'customer'      => rtrim($this->formated['customer']),
            'postal_code'   => $this->formated['postal_code']
        ];

        $addressData = json_decode(file_get_contents("https://viacep.com.br/ws/" . $this->formated['postal_code'] . "/json/"), true);
        
        $formated = $this->formated;

        $this->result = [
            'id'        => $this->formated['id'],
            'date'      => $this->formated['date'],
            'amount'    => $this->formated['amount'],
            'customer'  => [
                'name' => $this->formated['customer'],
                'address' => [ 
                    'street'        => $addressData['logradouro'],
                    'neighborhood'  => $addressData['bairro'],
                    'city'          => $addressData['localidade'],
                    'state'         => $addressData['uf'],
                    'postal_code'   => $addressData['cep']
                ]
            ],
            'installments' => collect(array_fill(0, $this->formated['installments'], 0))->map(function($installment, $index) use($formated) {
                
                // Se a data for sabado ou domingo, muda a data para segunda
                $date = new Carbon($formated['date']);
                $date->addDays($index * 30);
                if ($date->format('l') == 'Saturday') {
                    $date->addDays(2);
                }elseif($date->format('l') == 'Sunday') {
                    $date->addDays(1);
                }

                // A diferença de valor da parcela é passado para a primeira parcela
                $installmentAmout = $formated['amount'] / $formated['installments'];
                $installmentAmout = floor($installmentAmout * 100) / 100;
        
                $installmentDiff = $formated['amount'] - ($installmentAmout * $formated['installments']);
                $installmentDiff = round($installmentDiff, 2);
                
                if ($index == 0) {
                    $installmentAmout = $installmentAmout + $installmentDiff;
                }
                
                return [
                    'installment' => ++$index,
                    'amount' => number_format($installmentAmout, 2, '.', ''),
                    'date' => $date->format('Y-m-d')
                ];
            })
        ];
        return $this;
    }

    public function get()
    {
        return $this->result;
    }
}