<?php

namespace Application\Services;

use Application\Config\ClientCsv;
use Application\Models\Client;

class ClientCsvExportService
{
    public function stream(Client $clients, string $search = ''): void
    {
        $output=fopen('php://output','wb');
        if($output===false) throw new \RuntimeException('The export stream could not be opened.');

        // Match the supplied template: UTF-8 BOM, comma delimiter, minimal quoting and CRLF rows.
        fwrite($output,"\xEF\xBB\xBF");
        $headers=ClientCsv::headers();
        $this->writeRow($output,array_merge([ClientCsv::TITLE],array_fill(0,count($headers)-1,'')));
        $this->writeRow($output,$headers);

        $offset=0;
        do {
            $batch=$clients->getExportBatch($search,$offset,250);
            foreach($batch as $record) $this->writeRow($output,$this->mapRecord($record));
            $count=count($batch);
            $offset+=$count;
            if(function_exists('flush')) flush();
        } while($count===250);

        fclose($output);
    }

    public function mapRecord(array $record): array
    {
        $attributes=json_decode((string)($record['attributes']??'{}'),true);
        if(!is_array($attributes)) $attributes=[];
        $status=ucfirst(str_replace('_',' ',(string)($record['user_status']??'active'))).' client';
        $notes=trim((string)($record['notes']??''));
        if($notes!=='') $status.='; '.$notes;

        return [
            $this->safe((string)($record['company_name']?:$record['contact_name'])),
            $this->safe($status),
            $this->identifier((string)($record['company_number']??'')),
            $this->identifier($this->attribute($attributes,'ct_utr') ?: $this->attribute($attributes,'personal_utr') ?: (string)($record['tax_reference']??'')),
            $this->identifier($this->attribute($attributes,'vat_number')),
            $this->safe((string)($record['address']??'')),
            $this->safe((string)($record['directors']?:$record['contact_name'])),
            $this->safe((string)($record['email']??'')),
            $this->identifier((string)($record['phone']??'')),
            $this->date($this->attribute($attributes,'accounting_year_end')),
            $this->date((string)($record['filing_deadline']??'')),
            $this->safe($this->attribute($attributes,'vat_quarter')),
        ];
    }

    private function attribute(array $attributes,string $key): string
    {
        $value=$attributes[$key]??'';
        if(is_array($value)) $value=$value['value']??'';
        return trim((string)$value);
    }

    private function date(string $value): string
    {
        $value=trim($value);
        if($value==='') return '';
        $timestamp=strtotime($value);
        return $timestamp===false ? $this->safe($value) : date('D d M Y',$timestamp);
    }

    private function identifier(string $value): string
    {
        $value=$this->safe(trim($value));
        // Quoting alone does not stop Excel removing leading zeroes. An apostrophe
        // makes such identifiers explicit text without using an executable formula.
        if(preg_match('/^0\d+$/',$value)) return "'".$value;
        return $value;
    }

    private function safe(string $value): string
    {
        $value=str_replace("\0",'',trim($value));
        return preg_match('/^[\x00-\x20]*[=+\-@]/u',$value) ? "'".$value : $value;
    }

    private function writeRow($output,array $row): void
    {
        $temp=fopen('php://temp','w+b');
        if($temp===false) throw new \RuntimeException('A CSV row could not be generated.');
        fputcsv($temp,$row,',','"','');
        rewind($temp);
        $csv=stream_get_contents($temp);
        fclose($temp);
        fwrite($output,rtrim((string)$csv,"\r\n")."\r\n");
    }
}
