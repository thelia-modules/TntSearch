<?php
namespace TntSearch\Stemmer;

use TeamTNT\TNTSearch\Stemmer\Stemmer;

/**
 * Modification du stemmer standard qui vient avec TNTSearch pour supprimer les accents.
 *
 * @link http://snowball.tartarus.org/algorithms/french/stemmer.html
 * The original author is wamania
 *
 */

class FrenchStemmer implements Stemmer
{
    /**
     * All french vowels
     */
    protected static $vowels = ['a', 'e', 'i', 'o', 'u', 'y' ]; //, 'â', 'à', 'ë', 'é', 'ê', 'è', 'ï', 'î', 'ô', 'û', 'ù'];
    protected $word;

    /**
     * helper, contains stringified list of vowels
     * @var string
     */
    protected $plainVowels;

    /**
     * The original word, use to check if word has been modified
     * @var string
     */
    protected $originalWord;

    /**
     * RV value
     * @var string
     */
    protected $rv;

    /**
     * RV index (based on the beginning of the word)
     * @var int
     */
    protected $rvIndex;

    /**
     * R1 value
     * @var int
     */
    protected $r1;

    /**
     * R1 index (based on the beginning of the word)
     * @var int
     */
    protected $r1Index;

    /**
     * R2 value
     * @var int
     */
    protected $r2;

    /**
     * R2 index (based on the beginning of the word)
     * @var int
     */
    protected $r2Index;

    public static function stem($word)
    {
        return (new static)->analyze($word);
    }

    public function analyze($word)
    {
        $this->word = mb_strtolower($this->removeAccents($word));

        $this->plainVowels = implode('', static::$vowels);

        $this->step0();

        $this->rv();
        $this->r1();
        $this->r2();

        // to know if step1, 2a or 2b have altered the word
        $this->originalWord = $this->word;

        $nextStep = $this->step1();

        // Do step 2a if either no ending was removed by step 1, or if one of endings amment, emment, ment, ments was found.
        if (($nextStep == 2) || ($this->originalWord === $this->word) ) {
            $modified = $this->step2a();

            if (!$modified) {
                $this->step2b();
            }
        }

        if ($this->word != $this->originalWord) {
            $this->step3();

        } else {
            $this->step4();
        }

        $this->step5();
        $this->step6();
        $this->finish();

        return $this->word;
    }


    /**
     *  Assume the word is in lower case.
     *  Then put into upper case u or i preceded and followed by a vowel, and y preceded or followed by a vowel.
     *  u after q is also put into upper case. For example,
     *      jouer 		-> 		joUer
     *      ennuie 		-> 		ennuIe
     *      yeux 		-> 		Yeux
     *      quand 		-> 		qUand
     */
    private function step0()
    {
        $this->word = preg_replace('#([q])u#u', '$1U', $this->word);
        $this->word = preg_replace('#(['.$this->plainVowels.'])y#u', '$1Y', $this->word);
        $this->word = preg_replace('#y(['.$this->plainVowels.'])#u', 'Y$1', $this->word);
        $this->word = preg_replace('#(['.$this->plainVowels.'])u(['.$this->plainVowels.'])#u', '$1U$2', $this->word);
        $this->word = preg_replace('#(['.$this->plainVowels.'])i(['.$this->plainVowels.'])#u', '$1I$2', $this->word);
    }

    /**
     * Step 1
     * Search for the longest among the following suffixes, and perform the action indicated.
     *
     * @return integer Next step number
     */
    private function step1()
    {
        // ance   iqUe   isme   able   iste   eux   ances   iqUes   ismes   ables   istes
        //     delete if in R2
        if (($position = $this->search([
            'ances', 'iqUes', 'ismes', 'ables', 'istes', 'ance', 'iqUe','isme', 'able', 'iste', 'eux'
            ])) !== false) {
            if ($this->inR2($position)) {
                $this->word = mb_substr($this->word, 0, $position);
            }

            return 3;
        }

        // atrice   ateur   ation   atrices   ateurs   ations
        //      delete if in R2
        //      if preceded by ic, delete if in R2, else replace by iqU
        if (($position = $this->search(['atrices', 'ateurs', 'ations', 'atrice', 'ateur', 'ation'])) !== false) {
            if ($this->inR2($position)) {
                $this->word = mb_substr($this->word, 0, $position);

                if (($position2 = $this->searchIfInR2(['ic'])) !== false) {
                    $this->word = mb_substr($this->word, 0, $position2);
                } else {
                    $this->word = preg_replace('#(ic)$#u', 'iqU', $this->word);
                }
            }

            return 3;
        }

        // logie   logies
        //      replace with log if in R2
        if (($position = $this->search(['logies', 'logie'])) !== false) {
            if ($this->inR2($position)) {
                $this->word = preg_replace('#(logies|logie)$#u', 'log', $this->word);
            }

            return 3;
        }

        // usion   ution   usions   utions
        //      replace with u if in R2
        if (($position = $this->search(['usions', 'utions', 'usion', 'ution'])) !== false) {
            if ($this->inR2($position)) {
                $this->word = preg_replace('#(usion|ution|usions|utions)$#u', 'u', $this->word);
            }

            return 3;
        }

        // ence   ences
        //      replace with ent if in R2
        if (($position = $this->search(['ences', 'ence'])) !== false) {
            if ($this->inR2($position)) {
                $this->word = preg_replace('#(ence|ences)$#u', 'ent', $this->word);
            }

            return 3;
        }

        // issement   issements
        //      delete if in R1 and preceded by a non-vowel
        if (($position = $this->search(['issements', 'issement'])) != false) {
            if ($this->inR1($position)) {
                $before = $position - 1;
                $letter = mb_substr($this->word, $before, 1);

                if (! in_array($letter, static::$vowels)) {
                    $this->word = mb_substr($this->word, 0, $position);
                }
            }

            return 3;
        }

        // ement   ements
        //      delete if in RV
        //      if preceded by iv, delete if in R2 (and if further preceded by at, delete if in R2), otherwise,
        //      if preceded by eus, delete if in R2, else replace by eux if in R1, otherwise,
        //      if preceded by abl or iqU, delete if in R2, otherwise,
        //      if preceded by ièr or Ièr, replace by i if in RV
        if (($position = $this->search(['ements', 'ement'])) !== false) {
            if ($this->inRv($position)) {
                $this->word = mb_substr($this->word, 0, $position);
            }

            if (($position = $this->searchIfInR2(['iv'])) !== false) {
                $this->word = mb_substr($this->word, 0, $position);

                if (($position2 = $this->searchIfInR2(['at'])) !== false) {
                    $this->word = mb_substr($this->word, 0, $position2);
                }
            } elseif (($position = $this->search(['eus'])) !== false) {
                if ($this->inR2($position)) {
                    $this->word = mb_substr($this->word, 0, $position);
                } elseif ($this->inR1($position)) {
                    $this->word = preg_replace('#(eus)$#u', 'eux', $this->word);
                }
            } elseif (($position = $this->searchIfInR2(['abl', 'iqU'])) !== false) {
                $this->word = mb_substr($this->word, 0, $position);
            } elseif (($this->searchIfInRv(['ièr', 'Ièr'])) !== false) {
                $this->word = preg_replace('#(ièr|Ièr)$#u', 'i', $this->word);
            }

            return 3;
        }

        // ité   ités
        //      delete if in R2
        //      if preceded by abil, delete if in R2, else replace by abl, otherwise,
        //      if preceded by ic, delete if in R2, else replace by iqU, otherwise,
        //      if preceded by iv, delete if in R2
        if (($position = $this->search(['ités', 'ité'])) !== false) {
            // delete if in R2
            if ($this->inR2($position)) {
                $this->word = mb_substr($this->word, 0, $position);
            }

            // if preceded by abil, delete if in R2, else replace by abl, otherwise,
            if (($position = $this->search(['abil'])) !== false) {
                if ($this->inR2($position)) {
                    $this->word = mb_substr($this->word, 0, $position);
                } else {
                    $this->word = preg_replace('#(abil)$#u', 'abl', $this->word);
                }

                // if preceded by ic, delete if in R2, else replace by iqU, otherwise,
            } elseif (($position = $this->search(['ic'])) !== false) {
                if ($this->inR2($position)) {
                    $this->word = mb_substr($this->word, 0, $position);
                } else {
                    $this->word = preg_replace('#(ic)$#u', 'iqU', $this->word);
                }

                // if preceded by iv, delete if in R2
            } elseif (($position = $this->searchIfInR2(['iv'])) !== false) {
                $this->word = mb_substr($this->word, 0, $position);
            }

            return 3;
        }

        // if   ive   ifs   ives
        //      delete if in R2
        //      if preceded by at, delete if in R2 (and if further preceded by ic, delete if in R2, else replace by iqU)
        if (($position = $this->search(['ifs', 'ives', 'if', 'ive'])) !== false) {
            if ($this->inR2($position)) {
                $this->word = mb_substr($this->word, 0, $position);
            }

            if (($position = $this->searchIfInR2(['at'])) !== false) {
                $this->word = mb_substr($this->word, 0, $position);

                if (($position2 = $this->search(['ic'])) !== false) {
                    if ($this->inR2($position2)) {
                        $this->word = mb_substr($this->word, 0, $position2);
                    } else {
                        $this->word = preg_replace('#(ic)$#u', 'iqU', $this->word);
                    }
                }
            }

            return 3;
        }

        // eaux
        //      replace with eau
        if (($this->search(['eaux'])) !== false) {
            $this->word = preg_replace('#(eaux)$#u', 'eau', $this->word);

            return 3;
        }

        // aux
        //      replace with al if in R1
        if (($position = $this->search(['aux'])) !== false) {
            if ($this->inR1($position)) {
                $this->word = preg_replace('#(aux)$#u', 'al', $this->word);
            }

            return 3;
        }

        // euse   euses
        //      delete if in R2, else replace by eux if in R1
        if (($position = $this->search(['euses', 'euse'])) !== false) {
            if ($this->inR2($position)) {
                $this->word = mb_substr($this->word, 0, $position);
            } elseif ($this->inR1($position)) {
                $this->word = preg_replace('#(euses|euse)$#u', 'eux', $this->word);

            }

            return 3;
        }

        // amment
        //      replace with ant if in RV
        if ( ($position = $this->search(['amment'])) !== false) {
            if ($this->inRv($position)) {
                $this->word = preg_replace('#(amment)$#u', 'ant', $this->word);
            }
            return 2;
        }

        // emment
        //      replace with ent if in RV
        if (($position = $this->search(['emment'])) !== false) {
            if ($this->inRv($position)) {
                $this->word = preg_replace('#(emment)$#u', 'ent', $this->word);
            }

            return 2;
        }

        // ment   ments
        //      delete if preceded by a vowel in RV
        if (($position = $this->search(['ments', 'ment'])) != false) {
            $before = $position - 1;
            $letter = mb_substr($this->word, $before, 1);

            if ($this->inRv($before) && (in_array($letter, static::$vowels)) ) {
                $this->word = mb_substr($this->word, 0, $position);
            }

            return 2;
        }

        return 2;
    }

    /**
     * Step 2a: Verb suffixes beginning i
     *  In steps 2a and 2b all tests are confined to the RV region.
     *  Search for the longest among the following suffixes and if found, delete if preceded by a non-vowel.
     *      îmes   ît   îtes   i   ie   ies   ir   ira   irai   iraIent   irais   irait   iras   irent   irez   iriez
     *      irions   irons   iront   is   issaIent   issais   issait   issant   issante   issantes   issants   isse
     *      issent   isses   issez   issiez   issions   issons   it
     *  (Note that the non-vowel itself must also be in RV.)
     */
    private function step2a()
    {
        if (($position = $this->searchIfInRv([
                'îmes', 'îtes', 'ît', 'ies', 'ie', 'iraIent', 'irais', 'irait', 'irai', 'iras', 'ira', 'irent', 'irez', 'iriez',
                'irions', 'irons', 'iront', 'ir', 'issaIent', 'issais', 'issait', 'issant', 'issantes', 'issante', 'issants',
                'issent', 'isses', 'issez', 'isse', 'issiez', 'issions', 'issons', 'is', 'it', 'i'])) !== false) {

            $before = $position - 1;
            $letter = mb_substr($this->word, $before, 1);

            if ( $this->inRv($before) && (!in_array($letter, static::$vowels)) ) {
                $this->word = mb_substr($this->word, 0, $position);

                return true;
            }
        }

        return false;
    }

    /**
     * Do step 2b if step 2a was done, but failed to remove a suffix.
     * Step 2b: Other verb suffixes
     */
    private function step2b()
    {
        // é   ée   ées   és   èrent   er   era   erai   eraIent   erais   erait   eras   erez   eriez   erions   erons   eront   ez   iez
        //      delete
        if (($position = $this->searchIfInRv([
                'ées', 'èrent', 'erais', 'erait', 'erai', 'eraIent', 'eras', 'erez', 'eriez',
                'erions', 'erons', 'eront', 'era', 'er', 'iez', 'ez','és', 'ée', 'é'])) !== false) {

            $this->word = mb_substr($this->word, 0, $position);

            return true;
        }

        // âmes   ât   âtes   a   ai   aIent   ais   ait   ant   ante   antes   ants   as   asse   assent   asses   assiez   assions
        //      delete
        //      if preceded by e, delete
        if (($position = $this->searchIfInRv([
                'âmes', 'âtes', 'ât', 'aIent', 'ais', 'ait', 'antes', 'ante', 'ants', 'ant',
                'assent', 'asses', 'assiez', 'assions', 'asse', 'as', 'ai', 'a'])) !== false) {

            $before = $position - 1;
            $letter = mb_substr($this->word, $before, 1);

            if ( $this->inRv($before) && ($letter === 'e') ) {
                $this->word = mb_substr($this->word, 0, $before);
            } else {
                $this->word = mb_substr($this->word, 0, $position);
            }

            return true;
        }

        // ions
        //      delete if in R2
        if ( ($position = $this->searchIfInRv(array('ions'))) !== false) {
            if ($this->inR2($position)) {
                $this->word = mb_substr($this->word, 0, $position);
            }

            return true;
        }

        return false;
    }

    /**
     * Step 3: Replace final Y with i or final ç with c
     */
    private function step3()
    {
        $this->word = preg_replace('#(Y)$#u', 'i', $this->word);
        $this->word = preg_replace('#(ç)$#u', 'c', $this->word);
    }

    /**
     * Step 4: Residual suffix
     */
    private function step4()
    {
        //If the word ends s, not preceded by a, i, o, u, è or s, delete it.
        if (preg_match('#[^aiouès]s$#', $this->word)) {
            $this->word = mb_substr($this->word, 0, -1);
        }

        // In the rest of step 4, all tests are confined to the RV region.
        // ion
        //      delete if in R2 and preceded by s or t
        if ((($position = $this->searchIfInRv(['ion'])) !== false) && ($this->inR2($position)) ) {
            $before = $position - 1;
            $letter = mb_substr($this->word, $before, 1);

            if ( $this->inRv($before) && (($letter === 's') || ($letter === 't')) ) {
                $this->word = mb_substr($this->word, 0, $position);
            }

            return true;
        }

        // ier   ière   Ier   Ière
        //      replace with i
        if (($this->searchIfInRv(['ier', 'ière', 'Ier', 'Ière'])) !== false) {
            $this->word = preg_replace('#(ier|ière|Ier|Ière)$#u', 'i', $this->word);

            return true;
        }

        // e
        //      delete
        if (($this->searchIfInRv(['e'])) !== false) {
            $this->word = mb_substr($this->word, 0, -1);

            return true;
        }

        // ë
        //      if preceded by gu, delete
        if (($position = $this->searchIfInRv(['guë'])) !== false) {
            if ($this->inRv($position + 2)) {
                $this->word = mb_substr($this->word, 0, -1);

                return true;
            }
        }

        return false;
    }

    /**
     * Step 5: Undouble
     * If the word ends enn, onn, ett, ell or eill, delete the last letter
     */
    private function step5()
    {
        if ($this->search(['enn', 'onn', 'ett', 'ell', 'eill']) !== false) {
            $this->word = mb_substr($this->word, 0, -1);
        }
    }

    /**
     * Step 6: Un-accent
     * If the words ends é or è followed by at least one non-vowel, remove the accent from the e.
     */
    private function step6()
    {
        $this->word = preg_replace('#(é|è)([^'.$this->plainVowels.']+)$#u', 'e$2', $this->word);
    }

    /**
     * And finally:
     * Turn any remaining I, U and Y letters in the word back into lower case.
     */
    private function finish()
    {
        $this->word = str_replace(['I','U','Y'], ['i', 'u', 'y'], $this->word);
    }

    /**
     *  If the word begins with two vowels, RV is the region after the third letter,
     *  otherwise the region after the first vowel not at the beginning of the word,
     *  or the end of the word if these positions cannot be found.
     *  (Exceptionally, par, col or tap, at the begining of a word is also taken to define RV as the region to their right.)
     */
    protected function rv()
    {
        $length = mb_strlen($this->word);

        $this->rv = '';
        $this->rvIndex = $length;

        if ($length < 3) {
            return true;
        }

        // If the word begins with two vowels, RV is the region after the third letter
        $first = mb_substr($this->word, 0, 1);
        $second = mb_substr($this->word, 1, 1);

        if ( (in_array($first, static::$vowels)) && (in_array($second, static::$vowels)) ) {
            $this->rv = mb_substr($this->word, 3);
            $this->rvIndex = 3;

            return true;
        }

        // (Exceptionally, par, col or tap, at the begining of a word is also taken to define RV as the region to their right.)
        $begin3 = mb_substr($this->word, 0, 3);

        if (in_array($begin3, ['par', 'col', 'tap'])) {
            $this->rv = mb_substr($this->word, 3);
            $this->rvIndex = 3;

            return true;
        }

        //  otherwise the region after the first vowel not at the beginning of the word,
        for ($i = 1; $i < $length; ++$i) {
            $letter = mb_substr($this->word, $i, 1);

            if (in_array($letter, static::$vowels)) {
                $this->rv = mb_substr($this->word, ($i + 1));
                $this->rvIndex = $i + 1;

                return true;
            }
        }

        return false;
    }

    protected function inRv($position)
    {
        return ($position >= $this->rvIndex);
    }

    protected function inR1($position)
    {
        return ($position >= $this->r1Index);
    }

    protected function inR2($position)
    {
        return ($position >= $this->r2Index);
    }

    protected function searchIfInRv($suffixes)
    {
        return $this->search($suffixes, $this->rvIndex);
    }

    protected function searchIfInR2($suffixes)
    {
        return $this->search($suffixes, $this->r2Index);
    }

    protected function search($suffixes, $offset = 0)
    {
        $length = mb_strlen($this->word);

        if ($offset > $length) {
            return false;
        }

        foreach ($suffixes as $suffixe) {
            if ((($position = mb_strrpos($this->word, $suffixe, $offset)) !== false)
                && ((mb_strlen($suffixe) + $position) == $length)) {
                return $position;
            }
        }

        return false;
    }

    /**
     * R1 is the region after the first non-vowel following a vowel, or the end of the word if there is no such non-vowel.
     */
    protected function r1()
    {
        list($this->r1Index, $this->r1) = $this->rx($this->word);
    }

    /**
     * R2 is the region after the first non-vowel following a vowel in R1, or the end of the word if there is no such non-vowel.
     */
    protected function r2()
    {
        list($index, $value) = $this->rx($this->r1);

        $this->r2 = $value;
        $this->r2Index = $this->r1Index + $index;
    }

    /**
     * Common function for R1 and R2
     * Search the region after the first non-vowel following a vowel in $word, or the end of the word if there is no such non-vowel.
     * R1 : $in = $this->word
     * R2 : $in = R1
     */
    protected function rx($in)
    {
        $length = mb_strlen($in);

        // defaults
        $value = '';
        $index = $length;

        // we search all vowels
        $vowels = [];

        for ($i = 0; $i < $length; ++$i) {
            $letter = mb_substr($in, $i, 1);

            if (in_array($letter, static::$vowels)) {
                $vowels[] = $i;
            }
        }

        // search the non-vowel following a vowel
        foreach ($vowels as $position) {
            $after = $position + 1;
            $letter = mb_substr($in, $after, 1);

            if (!in_array($letter, static::$vowels)) {
                $index = $after + 1;
                $value = mb_substr($in, ($after + 1));

                break;
            }
        }

        return [$index, $value];
    }

    protected function removeAccents($string) {
        if ( !preg_match('/[\x80-\xff]/', $string) )
            return $string;

        $chars = array(
            // Decompositions for Latin-1 Supplement
            chr(195).chr(128) => 'A', chr(195).chr(129) => 'A',
            chr(195).chr(130) => 'A', chr(195).chr(131) => 'A',
            chr(195).chr(132) => 'A', chr(195).chr(133) => 'A',
            chr(195).chr(135) => 'C', chr(195).chr(136) => 'E',
            chr(195).chr(137) => 'E', chr(195).chr(138) => 'E',
            chr(195).chr(139) => 'E', chr(195).chr(140) => 'I',
            chr(195).chr(141) => 'I', chr(195).chr(142) => 'I',
            chr(195).chr(143) => 'I', chr(195).chr(145) => 'N',
            chr(195).chr(146) => 'O', chr(195).chr(147) => 'O',
            chr(195).chr(148) => 'O', chr(195).chr(149) => 'O',
            chr(195).chr(150) => 'O', chr(195).chr(153) => 'U',
            chr(195).chr(154) => 'U', chr(195).chr(155) => 'U',
            chr(195).chr(156) => 'U', chr(195).chr(157) => 'Y',
            chr(195).chr(159) => 's', chr(195).chr(160) => 'a',
            chr(195).chr(161) => 'a', chr(195).chr(162) => 'a',
            chr(195).chr(163) => 'a', chr(195).chr(164) => 'a',
            chr(195).chr(165) => 'a', chr(195).chr(167) => 'c',
            chr(195).chr(168) => 'e', chr(195).chr(169) => 'e',
            chr(195).chr(170) => 'e', chr(195).chr(171) => 'e',
            chr(195).chr(172) => 'i', chr(195).chr(173) => 'i',
            chr(195).chr(174) => 'i', chr(195).chr(175) => 'i',
            chr(195).chr(177) => 'n', chr(195).chr(178) => 'o',
            chr(195).chr(179) => 'o', chr(195).chr(180) => 'o',
            chr(195).chr(181) => 'o', chr(195).chr(182) => 'o',
            chr(195).chr(182) => 'o', chr(195).chr(185) => 'u',
            chr(195).chr(186) => 'u', chr(195).chr(187) => 'u',
            chr(195).chr(188) => 'u', chr(195).chr(189) => 'y',
            chr(195).chr(191) => 'y',
            // Decompositions for Latin Extended-A
            chr(196).chr(128) => 'A', chr(196).chr(129) => 'a',
            chr(196).chr(130) => 'A', chr(196).chr(131) => 'a',
            chr(196).chr(132) => 'A', chr(196).chr(133) => 'a',
            chr(196).chr(134) => 'C', chr(196).chr(135) => 'c',
            chr(196).chr(136) => 'C', chr(196).chr(137) => 'c',
            chr(196).chr(138) => 'C', chr(196).chr(139) => 'c',
            chr(196).chr(140) => 'C', chr(196).chr(141) => 'c',
            chr(196).chr(142) => 'D', chr(196).chr(143) => 'd',
            chr(196).chr(144) => 'D', chr(196).chr(145) => 'd',
            chr(196).chr(146) => 'E', chr(196).chr(147) => 'e',
            chr(196).chr(148) => 'E', chr(196).chr(149) => 'e',
            chr(196).chr(150) => 'E', chr(196).chr(151) => 'e',
            chr(196).chr(152) => 'E', chr(196).chr(153) => 'e',
            chr(196).chr(154) => 'E', chr(196).chr(155) => 'e',
            chr(196).chr(156) => 'G', chr(196).chr(157) => 'g',
            chr(196).chr(158) => 'G', chr(196).chr(159) => 'g',
            chr(196).chr(160) => 'G', chr(196).chr(161) => 'g',
            chr(196).chr(162) => 'G', chr(196).chr(163) => 'g',
            chr(196).chr(164) => 'H', chr(196).chr(165) => 'h',
            chr(196).chr(166) => 'H', chr(196).chr(167) => 'h',
            chr(196).chr(168) => 'I', chr(196).chr(169) => 'i',
            chr(196).chr(170) => 'I', chr(196).chr(171) => 'i',
            chr(196).chr(172) => 'I', chr(196).chr(173) => 'i',
            chr(196).chr(174) => 'I', chr(196).chr(175) => 'i',
            chr(196).chr(176) => 'I', chr(196).chr(177) => 'i',
            chr(196).chr(178) => 'IJ',chr(196).chr(179) => 'ij',
            chr(196).chr(180) => 'J', chr(196).chr(181) => 'j',
            chr(196).chr(182) => 'K', chr(196).chr(183) => 'k',
            chr(196).chr(184) => 'k', chr(196).chr(185) => 'L',
            chr(196).chr(186) => 'l', chr(196).chr(187) => 'L',
            chr(196).chr(188) => 'l', chr(196).chr(189) => 'L',
            chr(196).chr(190) => 'l', chr(196).chr(191) => 'L',
            chr(197).chr(128) => 'l', chr(197).chr(129) => 'L',
            chr(197).chr(130) => 'l', chr(197).chr(131) => 'N',
            chr(197).chr(132) => 'n', chr(197).chr(133) => 'N',
            chr(197).chr(134) => 'n', chr(197).chr(135) => 'N',
            chr(197).chr(136) => 'n', chr(197).chr(137) => 'N',
            chr(197).chr(138) => 'n', chr(197).chr(139) => 'N',
            chr(197).chr(140) => 'O', chr(197).chr(141) => 'o',
            chr(197).chr(142) => 'O', chr(197).chr(143) => 'o',
            chr(197).chr(144) => 'O', chr(197).chr(145) => 'o',
            chr(197).chr(146) => 'OE',chr(197).chr(147) => 'oe',
            chr(197).chr(148) => 'R',chr(197).chr(149) => 'r',
            chr(197).chr(150) => 'R',chr(197).chr(151) => 'r',
            chr(197).chr(152) => 'R',chr(197).chr(153) => 'r',
            chr(197).chr(154) => 'S',chr(197).chr(155) => 's',
            chr(197).chr(156) => 'S',chr(197).chr(157) => 's',
            chr(197).chr(158) => 'S',chr(197).chr(159) => 's',
            chr(197).chr(160) => 'S', chr(197).chr(161) => 's',
            chr(197).chr(162) => 'T', chr(197).chr(163) => 't',
            chr(197).chr(164) => 'T', chr(197).chr(165) => 't',
            chr(197).chr(166) => 'T', chr(197).chr(167) => 't',
            chr(197).chr(168) => 'U', chr(197).chr(169) => 'u',
            chr(197).chr(170) => 'U', chr(197).chr(171) => 'u',
            chr(197).chr(172) => 'U', chr(197).chr(173) => 'u',
            chr(197).chr(174) => 'U', chr(197).chr(175) => 'u',
            chr(197).chr(176) => 'U', chr(197).chr(177) => 'u',
            chr(197).chr(178) => 'U', chr(197).chr(179) => 'u',
            chr(197).chr(180) => 'W', chr(197).chr(181) => 'w',
            chr(197).chr(182) => 'Y', chr(197).chr(183) => 'y',
            chr(197).chr(184) => 'Y', chr(197).chr(185) => 'Z',
            chr(197).chr(186) => 'z', chr(197).chr(187) => 'Z',
            chr(197).chr(188) => 'z', chr(197).chr(189) => 'Z',
            chr(197).chr(190) => 'z', chr(197).chr(191) => 's'
        );

        $string = strtr($string, $chars);

        return $string;
    }
}
