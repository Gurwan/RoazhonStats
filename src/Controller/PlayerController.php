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
        $name = ucwords(str_replace('-',' ',$id));
        $url = 'http://www.sofascore.com/team/football/stade-rennais/1658/';
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, TRUE);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);

        curl_close($ch);

        preg_match_all("!player/[a-z][^\s]*/?!",$response,$ln);

        print_r($ln);

        return $this->render('player/player_view.html.twig', [
            'controller_name' => 'PlayerController', 'id' => $name
        ]);
    }


}
