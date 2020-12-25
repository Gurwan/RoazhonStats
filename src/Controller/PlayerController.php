<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class PlayerController extends AbstractController
{
    /**
     * @Route("/player", name="player")
     */
    public function index(): Response
    {
        //http://rougememoire.com/player/serhou-guirassy/
        $url = 'http://www.sofascore.com/team/football/stade-rennais/1658/'; //'http://www.sofascore.com/team/football/stade-rennais/1658/'
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        //"!https://www.sofascore.com/images/player/image_139228.png!"

        $response = curl_exec($ch);
        
        preg_match_all("!player/[a-z][^\s]*?/[0-9]*?!",$response,$matcheslnplayers);
        preg_match_all("!https://www.sofascore.com/images/player/image_[0-9]*?.png!",$response,$matchesimg);

        $images = array_unique($matchesimg[0]);
        $playersln = array_unique($matcheslnplayers[0]);
       

        $thetab = array();
        $j = 0;
        foreach ($images as $i){
            $tab = array($i,$playersln[$j]);
            array_push($thetab,$tab);
            $j++;
        }

        curl_close($ch);

        

        return $this->render('player/index.html.twig', [
            'controller_name' => 'PlayerController', 'tab' => $thetab
        ]);
    }

    /**
     * @Route("/player/{id}", name="player_show")
     */
    public function show_player($id): Response
    {
        

        $url = 'http://www.sofascore.com/team/football/stade-rennais/1658/';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);

        curl_close($ch);

        preg_match_all("!player/[a-z][^\s]*/?!",$response,$lna);
        $ln = array_unique($lna[0]);
     
        foreach($ln as $l){
            if(str_contains($l,$id)){
                $lnplayer = $l;
            }
        }

        $lnplayer = explode(">",$lnplayer,2);
        $lnplayer = $lnplayer[0];
        $lnplayer = explode('"',$lnplayer,2);
        $lnplayer = $lnplayer[0];

        $url = "http://www.sofascore.com/$lnplayer";
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);

        if($id=="sehrou-guirassy"){
            $id = "serhou-guirassy";
        }

        $name = ucwords(str_replace('-',' ',$id));
       

        preg_match_all("!https://www.sofascore.com/images/player/image_[0-9]*?.png!",$response,$matchesimg);
        $images = array_unique($matchesimg[0]);
    
        $dom = new \DOMDocument();
        @$dom-> loadHTML($response);

        $div = $dom->getElementsByTagName('div');
        $finder = new \DomXPath($dom);
        $sousf = $finder->query("//*[contains(@class, 'styles__DetailBoxContent-sc-1ss54tr-12 ExNjU')]"); 
        $dateNaissance = $sousf[1]->textContent;

        //bon pied manquant pour certains joueurs
        $surf = $finder->query("//*[contains(@class, 'styles__DetailBoxTitle-sc-1ss54tr-11 gMYPyy')]"); 
        if($surf->item(5)!==null){
            $number = $surf->item(5)->textContent;
            $poste = $surf->item(4)->textContent;
        } else {
            $number = $surf->item(4)->textContent;
            $poste = $surf->item(3)->textContent;
        }
        $taille = $surf->item(2)->textContent;
        $age = $surf->item(1)->textContent;
        $nationalite = $surf->item(0)->textContent;

        if($poste == "F"){
            $poste = "Attaquant";
        } else if($poste == "M"){
            $poste = "Milieu";
        } else if($poste == "D"){
            $poste = "Défenseur";
        } else {
            $poste = "Gardien";
        }

        //contrat
        /*
        $url = 'https://rougememoire.com/contracts'; //'http://www.sofascore.com/team/football/stade-rennais/1658/'
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $response = curl_exec($ch);
        curl_close($ch);
        */
      

        return $this->render('player/player_view.html.twig', [
            'controller_name' => 'PlayerController', 'id' => $name, 'photo' => $images[0], 'numero' => $number, 'poste' => $poste,
            'nation' => $nationalite, 'age' => $age, 'dateNaissance' => $dateNaissance, 'taille' => $taille
        ]);
    }


}
