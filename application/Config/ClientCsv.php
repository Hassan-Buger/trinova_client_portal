<?php

namespace Application\Config;

final class ClientCsv
{
    public const TITLE = 'Trinova Accounting - Full Client Audit (Business Clients)';
    public const FILING_DEADLINE_TYPE = 'Filing Deadline';
    public const VAT_DEADLINE_TYPE = 'VAT Deadline (Provisional)';
    public const VAT_DEADLINE_OFFSET_DAYS = 38;
    public const VAT_RULE_CONFIRMED = false;
    public const MAX_FILE_BYTES = 5_242_880;
    public const MAX_ROWS = 5000;

    public static function fields(): array
    {
        return [
            'client_name'=>['header'=>'Client Name','required'=>true,'aliases'=>['client name','company name','entity name']],
            'status_notes'=>['header'=>'Status / Notes','required'=>false,'aliases'=>['status / notes','status','notes']],
            'company_number'=>['header'=>'Company No.','required'=>false,'aliases'=>['company no.','company no','company number']],
            'utr'=>['header'=>'UTR','required'=>false,'aliases'=>['utr','corporation tax utr','ct utr']],
            'vat_number'=>['header'=>'VAT No.','required'=>false,'aliases'=>['vat no.','vat no','vat number']],
            'address'=>['header'=>'Registered Address','required'=>false,'aliases'=>['registered address','address']],
            'directors'=>['header'=>'Director(s) / Contact(s)','required'=>false,'aliases'=>['director(s) / contact(s)','directors','contacts']],
            'email'=>['header'=>'Email','required'=>false,'aliases'=>['email','email address']],
            'phone'=>['header'=>'Phone','required'=>false,'aliases'=>['phone','telephone','mobile']],
            'year_end'=>['header'=>'EOY (Year End)','required'=>false,'aliases'=>['eoy (year end)','year end','accounting year end']],
            'filing_deadline'=>['header'=>'Filing Deadline','required'=>false,'aliases'=>['filing deadline']],
            'vat_quarter'=>['header'=>'VAT Quarter','required'=>false,'aliases'=>['vat quarter','vat quarters']],
        ];
    }

    public static function headers(): array { return array_column(self::fields(), 'header'); }

    public static function defaultMapping(array $headers): array
    {
        $mapping=[];
        foreach($headers as $index=>$header){
            $normalized=self::normalize((string)$header);
            foreach(self::fields() as $key=>$field){
                if(in_array($normalized,array_map([self::class,'normalize'],$field['aliases']),true)){$mapping[$key]=(int)$index;break;}
            }
        }
        return $mapping;
    }

    private static function normalize(string $value): string
    {
        return preg_replace('/\s+/',' ',strtolower(trim($value," \t\n\r\0\x0B\xEF\xBB\xBF"))) ?? '';
    }
}
