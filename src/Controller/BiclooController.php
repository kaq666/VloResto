<?php

namespace App\Controller;

use Symfony\Component\HttpFoundation\Response;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class BiclooController extends AbstractController
{
    public function number()
    {
        $json = file_get_contents("https://data.nantesmetropole.fr/explore/dataset/234400034_070-008_offre-touristique-restaurants-rpdl@paysdelaloire/download/?format=json&timezone=Europe/Berlin");
        $data = json_decode($json, true);

        $bicloos = $this->get_bicloos();

        $localisations = array();
        foreach ($data as $record) {
            $localisation = [$record['fields']['latitude'], $record['fields']['longitude']];
            $distance = -1;
            $nbBicloo = 0;
            $nbStands = 0;
            $adr = "";
            foreach ($bicloos as $bicloo) {
                $d = $this->distance($localisation[0], $localisation[1], $bicloo[0][0], $bicloo[0][1]);
                if ($d < $distance || $distance == -1) {
                    $distance = $d;
                    $nbBicloo = $bicloo[1];
                    $nbStands = $bicloo[2];
                }
            }
            if(isset($record['fields']['adresse2'])) {
                $adr = $record['fields']['adresse2'];
            }
            $data = [$record['fields']['latitude'], $record['fields']['longitude'], round($distance), $nbBicloo, $nbStands, $record['fields']['nomoffre'], $adr];
            array_push($localisations, $data);
        }

        return $this->render('bicloo.html.twig', [
            'localisations' => $localisations
        ]);
    }

    public function get_bicloos()
    {
        $json = file_get_contents("https://data.nantesmetropole.fr/api/records/1.0/search/?dataset=244400404_stations-velos-libre-service-nantes-metropole-disponibilites&rows=-1&facet=banking&facet=bonus&facet=status&facet=contract_name");
        $data = json_decode($json, true);

        $localisations = array();
        foreach ($data['records'] as $record) {
            array_push($localisations, [$record['fields']['position'], $record['fields']['available_bikes'], $record['fields']['available_bike_stands']]);   
        }
        
        return $localisations;
    }

    public function distance($lat1, $lng1, $lat2, $lng2, $unit = 'm')
    {
        $earth_radius = 6378137;   // Terre = sphère de 6378km de rayon
        $rlo1 = deg2rad($lng1);
        $rla1 = deg2rad($lat1);
        $rlo2 = deg2rad($lng2);
        $rla2 = deg2rad($lat2);
        $dlo = ($rlo2 - $rlo1) / 2;
        $dla = ($rla2 - $rla1) / 2;
        $a = (sin($dla) * sin($dla)) + cos($rla1) * cos($rla2) * (sin($dlo) * sin($dlo));
        $d = 2 * atan2(sqrt($a), sqrt(1 - $a));

        $meter = ($earth_radius * $d);
        if ($unit == 'k') {
            return $meter / 1000;
        }

        return $meter;
    }
}