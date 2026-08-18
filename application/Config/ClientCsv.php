<?php

namespace Application\Config;

final class ClientCsv
{
    public const TITLE = 'Trinova Accounting - Full Client Audit (Business Clients)';
    public const FILING_DEADLINE_TYPE = 'Filing Deadline';
    public const CONFIRMATION_STATEMENT_DEADLINE_TYPE = 'Confirmation Statement Deadline';
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
            'paye_reference'=>['header'=>'PAYE Reference','required'=>false,'aliases'=>['paye reference','paye ref','paye ref number','paye ref number.']],
            'paye_office_number'=>['header'=>'PAYE Office Number','required'=>false,'aliases'=>['paye office number','paye office no','paye office no.']],
            'address'=>['header'=>'Registered Address','required'=>false,'aliases'=>['registered address','address']],
            'directors'=>['header'=>'Director(s) / Contact(s)','required'=>false,'aliases'=>['director(s) / contact(s)','director','directors','contact','contacts','director 1','director 2','director 3','director 4','director 5']],
            'email'=>['header'=>'Email','required'=>false,'aliases'=>['email','email address']],
            'phone'=>['header'=>'Phone','required'=>false,'aliases'=>['phone','telephone','mobile']],
            'year_end'=>['header'=>'EOY (Year End)','required'=>false,'aliases'=>['eoy (year end)','year end','accounting year end','end of year date']],
            'filing_deadline'=>['header'=>'Filing Deadline','required'=>false,'aliases'=>['filing deadline','accounts deadline','accounts dealine']],
            'confirmation_statement_date'=>['header'=>'Confirmation Statement Date','required'=>false,'aliases'=>['confirmation statement date','confirmation statement deadline']],
            'vat_return_frequency'=>['header'=>'VAT Return Frequency','required'=>false,'aliases'=>['vat return frequency','vat return qtr/monthly','vat return quarter/monthly']],
            'vat_quarter'=>['header'=>'VAT Quarter Pattern','required'=>false,'aliases'=>['vat quarter','vat quarters','vat quarter pattern','qtr1']],
        ];
    }

    public static function headers(): array { return array_column(self::fields(), 'header'); }

    public static function defaultMapping(array $headers): array
    {
        $mapping=[];
        foreach($headers as $index=>$header){
            $normalized=self::normalize((string)$header);
            foreach(self::fields() as $key=>$field){
                if(!isset($mapping[$key])&&in_array($normalized,array_map([self::class,'normalize'],$field['aliases']),true)){$mapping[$key]=(int)$index;break;}
            }
        }
        return $mapping;
    }

    private static function normalize(string $value): string
    {
        return preg_replace('/\s+/',' ',strtolower(trim($value," \t\n\r\0\x0B\xEF\xBB\xBF"))) ?? '';
    }
}
