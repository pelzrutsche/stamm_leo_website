; TAGS.TAG
;
; This file defines the "tags" (tokens) used in the content files,
; the parser shall replace when generating the ages.
;
; Diese Datei definiert in den Content-Dateien benutzten "Tags" (Begriffe),
; die der Parser beim Generieren der Seiten ersetzen soll.
;
; Comments can be added in a line of their own, biginning with a semicolon ";"
; Kommentare sind m�glich, jedoch nur als eigene Zeile, beginnend mit einem Semikolon ";"
;
; Usage:
;token to change        :=  result
;
; Gebrauch:
;zu ersetzender Bgriff  :=  Ergebnis

; HTML entities (decimal values used because of highest compatibility)
; HTML-Entities (Sonderzeichen usw.) (Dezimal-�quivalent, zu bevorzugen wegen der h�chsten Kompatibilt�t)
; German special characters / deutsche Sonderzeichen
�         := &#223;
�         := &#196;
�         := &#214;
�         := &#220;
�         := &#228;
�         := &#246;
�         := &#252;
; French special characters / franz�sische Sonderzeichen
�         := &#192;
�         := &#224;
�         := &#194;
�         := &#226;
�         := &#200;
�         := &#232;
�         := &#202;
�         := &#234;
�         := &#201;
�         := &#233;
�         := &#203;
�         := &#235;
�         := &#206;
�         := &#238;
�         := &#207;
�         := &#239;
�         := &#212;
�         := &#244;
�         := &#217;
�         := &#249;
�         := &#219;
�         := &#251;
�         := &#199;
�         := &#231;
�         := &#230;
; other special characters / sonstige Sonderzeichen
�         := &#8364;
�         := &#169;
�         := &#174;
�         := &#178;
�         := &#179;
�         := &#8482;
�         := &#167;
...       := &#8230;
;'s       := &#8217;s

; alternatively you may use entity names:
; alternativ k�nnen Entity-namen benutzt werden:
;�         := &szlig;
;�         := &Auml;
;�         := &Ouml;
;�         := &Uuml;
;�         := &auml;
;�         := &ouml;
;�         := &uuml;

[PIC "$url,$alt"]:=<img src="$url" alt="$alt" />

<q>        := &#8222;
</q>       := &#8221;
;---        := &#8212;

&nbsp;     := &#160;
<LZ>       := &#160;
<SPACE>    := &#160;
&sect;     := &#167;
&reg;      := &#174;
<br>       := <br />
<NL>       := <br />
<NZ>       := <br />
<WICHTIG>  := <strong>
</WICHTIG> := </strong>
<STRONG>   := <strong>
</STRONG>  := </strong>
<US>       := <h3 class="us">
</US>      := </h3>

; Search / Volltextsuche
<NO_DATA_DIR>         := <p>&nbsp;</p><p><span style="color:#DC143C;font-weight:bold">You did not specify any data directory!<br />
                         Sie haben kein Datenverzeichnis definiert!</span></p>

<NO_SEARCH_TERM>      := <p>&nbsp;</p><p><span style="color:#DC143C;font-weight:bold">You did not enter any search term!</span><br />
                         Please enter one or more search terms, and try again.</p>
                         <p><span style="color:#DC143C;font-weight:bold">Sie haben keinen Suchbegriff eingegeben!</span><br />
                         Geben Sie einen oder mehrere Suchbegriffe ein und versuchen Sie es nochmals.</p>

<SHORT_SEARCH_TERM>   := <p>&nbsp;</p><p><span style="color:#DC143C;font-weight:bold">The search term you entered was too short!</span><br />
                         Your search term should be at least <strong>three character</strong> in length.</span></p>
                         <p><span style="color:#DC143C;font-weight:bold">Sie haben einen zu kurzen Suchbegriff eingegeben!</span><br />
                         Ihr Suchbegriff sollte wenigstens <strong>drei Zeichen</strong> lang sein.</p>

<SEARCH_TERM_NONO>    := <p>&nbsp;</p><p>For <em style="color:#DC143C;font-weight:bold">this</em> you will have to look somewhere else!</p>
                         <p><em style="color:#DC143C;font-weight:bold">Danach</em> werden Sie wohl woanders suchen m�ssen!</p>

<TERM_EXCLUDED_PRE>   := <p>&nbsp;</p><p>The word / Der Begriff <span style="color:darkorange;font-weight:bold"><blockquote>

<TERM_EXCLUDED_PAST>  := </blockquote></span> was <span style="color:darkorange;font-weight:bold">not considered</span>, as it appears too often.<br />
                         </span> wurde <span style="color:darkorange;font-weight:bold">nicht ber�cksichtigt</span>, da er zu h�ufig vorkommt.</p>

<NO_SEARCH_RESULT>    := <p>&nbsp;</p><p><span style="color:black;font-weight:bold">No results found for you seach.</span><br />
                         Please try another search term.</p>
                         <p><span style="color:black;font-weight:bold">Suchbegriff nicht gefunden.</span><br />
                         Bitte �berpr�fen Sie Ihren Suchbegriff und starten Sie eine neue Suche.</p>

<SEARCH_PREV>         := &laquo; Go back / Zur�ck
<SEARCH_MIDDLE>       := &nbsp;&nbsp;|&nbsp;&nbsp;
<SEARCH_NEXT>         := Next / Weiter &raquo;

<RANK_1>              := <img src="$home/gif/1star.gif" border="0" width="15" height="15" alt="1 *" />
<RANK_2>              := <img src="$home/gif/2star.gif" border="0" width="30" height="15" alt="2 **" />
<RANK_3>              := <img src="$home/gif/3star.gif" border="0" width="45" height="15" alt="3 ***" />
<RANK_4>              := <img src="$home/gif/4star.gif" border="0" width="60" height="15" alt="4 ****" />
<RANK_5>              := <img src="$home/gif/5star.gif" border="0" width="75" height="15" alt="5 *****" />

;lastedited
<UPDATED>   :=  <!-- PAXPHP updatedon
                $edited = date("m/d/y", filemtime($DEFAULTS->DOCUMENT_ROOT.$CHECK_PAGE->path.'/'.$CHECK_PAGE->name));
                echo '<span style="font-size:small">Last updated: '.$edited.'</span>';
                PAXPHP updatedon -->
<EDITIERT>  :=  <!-- PAXPHP editiertam
                $edited = date("d.m.y", filemtime($DEFAULTS->DOCUMENT_ROOT.$CHECK_PAGE->path.'/'.$CHECK_PAGE->name));
                echo '<span style="font-size:small">Zuletzt ge&#228;ndert: '.$edited.'</span>';
                PAXPHP editiertam -->

