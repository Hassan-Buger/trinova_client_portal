<?php
namespace Application\Config;

final class DirectorCsv
{
    public const TITLE='Trinova - Directors of Limited Company Clients';
    public const MAX_FILE_BYTES=5242880;
    public const MAX_ROWS=5000;
    public static function fields():array{return [
        'name'=>['header'=>'Director Name','required'=>true,'aliases'=>['director name','director','full name','customer name','customer']],
        'companies'=>['header'=>'Linked Company/ies','required'=>true,'aliases'=>['linked company/ies','linked companies','linked company','company name','company','companies']],
        'utr'=>['header'=>'UTR','required'=>false,'aliases'=>['utr','director utr','unique taxpayer reference']],
        'phone'=>['header'=>'Phone','required'=>false,'aliases'=>['phone','telephone','mobile','phone number']],
        'email'=>['header'=>'Email','required'=>false,'aliases'=>['email','email address']],
        'address'=>['header'=>'Address','required'=>false,'aliases'=>['address','residential address','home address']],
        'id_number'=>['header'=>'Id Number','required'=>false,'aliases'=>['id number','identification number']],
        'verification_number'=>['header'=>'Companies House personal verification number','required'=>false,'aliases'=>['companies house personal verification number','personal verification number','ch verification number']],
    ];}
    public static function headers():array{return array_column(self::fields(),'header');}
    public static function mapping(array $headers):array{
        $map=[];
        foreach($headers as $i=>$h){
            $norm=self::normalize((string)$h);
            foreach(self::fields() as $key=>$f){
                if(in_array($norm,array_map([self::class,'normalize'],$f['aliases']),true)){
                    if($key==='companies'){
                        if(!isset($map['companies']))$map['companies']=[];
                        if(is_array($map['companies'])&&!in_array((int)$i,$map['companies'],true))$map['companies'][]=(int)$i;
                    }else{
                        if(!isset($map[$key]))$map[$key]=(int)$i;
                    }
                    break;
                }
            }
        }
        return $map;
    }
    public static function normalize(string $value):string{$value=str_replace(["\xEF\xBB\xBF","\t"],['',' '],$value);return trim(preg_replace('/[^a-z0-9]+/i',' ',strtolower($value))??'');}
}
