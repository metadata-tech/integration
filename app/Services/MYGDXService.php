<?php

namespace App\Services;
use App\Models\APILog;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;

class MYGDXService {

    public function __construct()
    {

    }

    public function buildXMLBody($subsystemCode, $serviceCode, $nokp, $tahun)
    {
        return '
                <soapenv:Envelope xmlns:soapenv="http://schemas.xmlsoap.org/soap/envelope/" xmlns:xro="http://x-road.eu/xsd/xroad.xsd" xmlns:iden="http://x-road.eu/xsd/identifiers" xmlns:v1="http://x-road.eu/xsd/connector/MPM-SSKSM-R-KeputusanMUET/v1">
                <soapenv:Header>
                    <xro:client iden:objectType="SUBSYSTEM">
                        <iden:xRoadInstance>MYGDX</iden:xRoadInstance>
                        <iden:memberClass>GOV</iden:memberClass>
                        <iden:memberCode>SPP</iden:memberCode>
                        <!--Optional:-->
                        <iden:subsystemCode>'.$subsystemCode.'</iden:subsystemCode>
                    </xro:client>
                    <xro:service iden:objectType="SERVICE">
                        <iden:xRoadInstance>MYGDX</iden:xRoadInstance>
                        <iden:memberClass>GOV</iden:memberClass>
                        <iden:memberCode>MPM</iden:memberCode>
                        <!--Optional:-->
                        <iden:subsystemCode>SSKSM</iden:subsystemCode>
                        <iden:serviceCode>'.$serviceCode.'</iden:serviceCode>
                        <!--Optional:-->
                        <iden:serviceVersion>v1</iden:serviceVersion>
                    </xro:service>
                    <xro:userId>mygdx</xro:userId>
                    <xro:id>27082025</xro:id>
                    <xro:protocolVersion>4.0</xro:protocolVersion>
                </soapenv:Header>
                <soapenv:Body>
                    <v1:'.$serviceCode.'>
                        <nokp>'.$nokp.'</nokp>
                        <tahun>'.$tahun.'</tahun>
                    </v1:'.$serviceCode.'>
                </soapenv:Body>
                </soapenv:Envelope>
                ';
    }

    public function getMUET($nokp, $tahun)
    {
        $subsystemCode = config('services.mygdx.subsystem_code');
        $serviceCode = 'MPM-SSKSM-R-MPMFikMUET';                
        
        // 2. Sediakan Body XML (SOAP Envelope)
        $xmlBody = $this->buildXMLBody($subsystemCode, $serviceCode, $nokp, $tahun);
        return $this->panggilMyGDX($xmlBody);
    }

    public function getSTPM($nokp, $tahun)
    {
        $subsystemCode = config('services.mygdx.subsystem_code');
        $serviceCode = 'MPM-SSKSM-R-MPMFikSTPM';                
        
        // 2. Sediakan Body XML (SOAP Envelope)
        $xmlBody = $this->buildXMLBody($subsystemCode, $serviceCode, $nokp, $tahun);
        return $this->panggilMyGDX($xmlBody);
    }

    public function panggilMyGDX($xmlBody)
    {
        $certPath = base_path('resources/MyGDX/espp.crt');
        $keyPath = base_path('resources/MyGDX/espp.key');

        $url = 'https://spp.mygdx.gov.my';
        $headers = [
            'Content-Type' => 'application/soap+xml',
            'x-road-client' => 'MYGDX/GOV/SPP/' . config('services.mygdx.subsystem_code'),
        ];

        $log = APILog::create([
            'method' => 'POST',
            'input' => ['xml_body' => $xmlBody],
            'headers' => $headers,
            'url' => $url,
            'user_id' => Auth::check() ? Auth::id() : null
        ]);

        $response = Http::withOptions([
            'cert' => $certPath,
            'ssl_key' => $keyPath,
            'timeout' => 30,
        ])
        ->withHeaders($headers)
        ->withoutVerifying()
        ->withBody($xmlBody, 'text/xml')
        ->post($url);

        $log->update([
            'output' => $response->successful() ? $response->body() : $response->body(),
            'status_code' => $response->getStatusCode()
        ]);

        if ($response->successful()) {
            $data = $response->body();
            $cleanedXmlString = preg_replace("/>\s*</", "><", $data);

            libxml_use_internal_errors(true);
            $dom = new \DOMDocument();
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = false;

            if (!$dom->loadXML($cleanedXmlString)) {
                $errors = libxml_get_errors();
                libxml_clear_errors();
                return ['error' => 'Failed to parse XML', 'errors' => array_map(fn($e) => $e->message, $errors), 'xml' => $cleanedXmlString];
            }

            $array = $this->domToArray($dom->documentElement);
            return $array;
        } else {
            return $response->throw();
        }
    }

    /**
     * Convert DOMElement to array recursively
     */
    private function domToArray($node)
    {
        $output = [];

        switch ($node->nodeType) {
            case XML_CDATA_SECTION_NODE:
            case XML_TEXT_NODE:
                return trim($node->textContent);

            case XML_ELEMENT_NODE:
                // Process child nodes
                $children = [];
                $textContent = '';

                foreach ($node->childNodes as $child) {
                    $v = $this->domToArray($child);

                    if (isset($child->tagName)) {
                        $t = $child->tagName;
                        if (!isset($children[$t])) {
                            $children[$t] = [];
                        }
                        $children[$t][] = $v;
                    } elseif ($v !== null && $v !== '') {
                        $textContent .= $v;
                    }
                }

                // Process attributes
                if ($node->attributes->length) {
                    $output['@attributes'] = [];
                    foreach ($node->attributes as $attrName => $attrNode) {
                        $output['@attributes'][$attrName] = $attrNode->value;
                    }
                }

                // If there are child elements, use them; otherwise use text content
                if (count($children) > 0) {
                    foreach ($children as $tag => $values) {
                        // If only one child with this tag, don't wrap in array
                        $output[$tag] = count($values) === 1 ? $values[0] : $values;
                    }
                } elseif (trim($textContent) !== '') {
                    return trim($textContent);
                }

                break;
        }

        return $output;
    }

}
