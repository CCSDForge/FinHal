<?php

/**
 * Created by PhpStorm.
 * User: sdenoux
 * Date: 13/12/16
 * Time: 14:49
 */
class Hal_Document_Meta_Domain extends Hal_Document_Meta_Complex
{

    /**
     * On transforme les domaines inter en domaines
     *
     * @param $data
     * @return mixed
     */
    static public function mergeInterDomains($data)
    {
        $toreturn = $data;

        if (array_key_exists('domain_inter', $data)) {

            unset($toreturn['domain_inter']);

            foreach($data['domain_inter'] as $di) {
                $toreturn['domain'][] = $di;
            }
        }

        return $toreturn;
    }

    /**
     * @param $domain
     * @param $domainList sous forme de tableau associatif domain=>sousdomaines :
       [ sde => [sde.bio => [sde.bio.che, sde.bio.bib],
                 sde.mac,
                 etc],
         sbe => [sbe.truc,
                 etc]
       ]
     * @return bool
     */
    static public function isAccepted($domain, $domainList)
    {
        $jsonList = json_encode($domainList);

        if (false === strpos($jsonList, "\"".$domain."\"")) {
            return false;
        } else {
            return true;
        }

    }

    /**
     * On sépare les domaines inter et les domaines
     *
     * @param $data
     * @param $form
     * @return mixed
     */
    static public function explodeInterDomains($data, $form)
    {
        $domainList = $form->getElement('domain')->getData();

        if (array_key_exists('domain', $data)) {
            foreach ($data['domain'] as $i => $domain) {

                // La valeur ne se trouve ni dans les domaines, ni dans les sous-domaines
                if (! self::isAccepted($domain, $domainList)) {
                    // Si le domaine n'est pas accepté pour ce champ de formulaire, on le passe en domain_inter
                    $data['domain_inter'][] = $domain;
                    unset($data['domain'][$i]);
                }
            }

            // On reset les index pour avoir 0, 1, 2, 3, etc plutôt que 2, 4, 5 selon les sous-domaines enlevés
            $data['domain'] = array_values($data['domain']);
        }

        return $data;
    }
}
