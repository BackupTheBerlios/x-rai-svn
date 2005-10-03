<!--


-->
<?
// <image filename="/images/plug_types/elec_4.gif">

function print_before($s) { print "<html:span>$s</html:span>"; }
$tagstack = array();

function in_tag(&$tagstack, &$list) {
   $size = sizeof($tagstack);
   $listsize = sizeof($list);
   if ($listsize > $size) return false;
   for($i = sizeof($list); $i > 0; $i--) if ($tagstack[$size-$i] != $list[$listsize-$i]) return false;
   return true;
}

function collectionEndElement($name) {
   global $tagstack;
   array_pop($tagstack);
}

function collectionStartElement($name, $attrs) {
   global $media_url, $collection, $base_url,$directory, $tagstack;
   switch($name) {
      case "general": print_before("General"); break;
      case "transport": print_before("Transport"); break;
      case "government": print_before("Government"); break;
      case "environment": print_before("Environment"); break;
      case "society": print_before("Society"); break;
      case "economy": print_before("Economy"); break;
      case "money": print_before("Money"); break;
      case "pre-departure": print_before("Pre-departure"); break;
      case "weather": print_before("Weather"); break;
      case "communication": print_before("Communication"); break;
      case "transport": print_before("Transport"); break;
      case "culture": print_before("Culture"); break;
      case "images": print_before("Images"); break;
      case "points_of_interest": print_before("Points of interest"); break;
      case "attractions": print_before("Attractions"); break;
      case "factoids": print_before("Facts"); break;
      case "eat": print_before("Eat"); break;
      case "night": print_before("Night"); break;
      case "see": print_before("See"); break;
      case "shop": print_before("Shop"); break;
      case "sleep": print_before("Sleep"); break;
      case "health": print_before("Health"); break;
      case "doing_business": print_before("doing business"); break;
      case "warning": print_before("Warning"); break;
      case "currency": print_before("Currency"); break;
      case "timezones": print_before("Timezones"); break;
      case "introduction": print_before("Introduction"); break;
      case "weights_measures": print_before("Weight measures"); break;
      case "leaders": print_before("Leaders"); break;
      case "language_spokens": print_before("Spoken languages"); break;
      case "religion": print_before("Religion"); break;
      case "daylight_savings": print_before("Daylight savings times"); break;
      case "orientation": print_before("Orientation"); break;
      case "geography": print_before("Geography"); break;
      case "flora": print_before("Flora"); break;
      case "fauna": print_before("Faune"); break;
      case "political_region": print_before("Political region"); break;
      case "geology": print_before("Geology"); break;
      case "getting_there_and_away": print_before("Getting there and away"); break;
      case "getting_around": print_before("Getting around"); break;
      case "overview": print_before("Overview"); break;
      case "recent": print_before("Recent history"); break;
      case "modern": print_before("Modern history"); break;
      case "pre_20c": print_before("'Pre twentieth century' history"); break;
      case "females": print_before("Females"); break;
      case "gay_lesbian": print_before("Gay/lesbian"); break;
      case "disabled": print_before("Disabled"); break;
      case "kids": print_before("Kids"); break;
      case "people": print_before("People"); break;
      case "major_industries": print_before("Major industries"); break;
      case "trading_partners": print_before("Trading partners"); break;
      case "tipping": print_before("Tipping"); break;
      case "preferred_form": print_before("Preferred form"); break;
      case "tips": print_before("Tips"); break;
      case "changing": print_before("Changing money"); break;
      case "relative_costs": print_before("Relative costs"); break;
      case "sample_prices": print_before("Sample prices "); break;
      case "visas_overview": print_before("Visa requirements"); break;
      case "electricity": print_before("Electricity"); break;
      case "when_to_go": print_before("When to go"); break;
      case "premium_depurture_countries": print_before("Premium departure countries: "); break;
      case "customs": print_before("Customs"); break;
      case "duty_free": print_before("Duty free shopping"); break;
      case "event": print_before("Event"); break;
      case "dangers_and_annoyances": print_before("Dangers and annoyances"); break;
      case "conditions": print_before("Conditions"); break;
      case "business_area": print_before("Business area"); break;
      case "etiquette": print_before("Etiquette"); break;
      case "books": print_before("Books"); break;
      case "periodicals": print_before("Periodicals"); break;
      case "radio_stations": print_before("Radio stations"); break;
      case "tv_systems": print_before("TV systems"); break;
      case "dvd_zones": print_before("DVD zones"); break;
      case "point_of_interest": print_before("Point of interest: "); break;
      case "telephone":print_before("Telephone"); break;
      case "leader": print_before("Leader "); break;
      case "language_spoken": print_before("Language "); break;
      case "gmt_utc": print_before("Timezone (relative to GMT/UTC): "); break;
      case "name:before": print_before("Timezone name: "); break;
      case "coins": print_before("Coins"); break;
      case "notes": print_before("Notes"); break;
      case "gpd": print_before("Gdp"); break;
      case "system": print_before("System: "); break;
      case "start": print_before("Start: "); break;
      case "end": print_before("End: "); break;
      case "low": print_before("low prices: "); break;
      case "mid": print_before("mid prices: "); break;
      case "high": print_before("high prices: "); break;
      case "deluxe": print_before("deluxe prices: "); break;
      case "amount": print_before($attrs["currency"]);
      case "postcode": print_before("Postal code: "); break;
//

      case "name":
         if (sizeof($tagstack) < 1) break;
         switch ($tagstack[sizeof($tagstack)-1]) {
            case "tour": print_before("Tour"); break;
            case "map": print_before("Map of"); break;
            case "destination": print_before("Destination"); break;
            case "currency": print_before("Name:"); break;
         }
         break;

      case "type":
         if (sizeof($tagstack) < 1) break;
         switch ($tagstack[sizeof($tagstack)-1]) {
            case "getting_there_and_away": case "getting_arround": print_before("Topic type: "); break;
         }
         break;

      case "title":
         if (sizeof($tagstack) < 1) break;
         switch ($tagstack[sizeof($tagstack)-1]) {
            case "getting_arround": case "getting_there_and_away": print_before("Topic title: "); break;
         }
         break;

      case "plugs": print_before("Plugs"); break;
      case "plug": print_before("Plug"); break;
      case "activity": print_before("Activity"); break;
      case "tours": print_before("Tours"); break;
/*      currency name:before {content: "Name: ";        font-weight:normal; }
      currency code:before {content: "Code: ";        font-weight:normal; }
      currency symbol:before {content: "Symbol: ";    font-weight:normal; }
      currency unit:before {content: "Unit: ";        font-weight:normal; }*/
      case "voltage": print_before("Voltage: ");
      case "hz": print_before("Frequency (Hz): ");
/*      leader name:before {content: "Name: ";  font-weight:normal; }
      leader title:before {content: "Title: ";        font-weight:normal; }
      leader type:before {content: "Type of position: ";      font-weight:normal; }*/
      case "room": print_before("Rooms: "); break;
      case "meal": print_before("Meals: "); break;
/*      activity type:before {content: "Type of activity: ";    font-weight:normal; }
      activity subtype:before {content: "Subtype: ";  font-weight:normal; }
      language_spokens type:before { content: "Type: ";       font-weight:normal; }
      language_spokens name:before { content: "Name: ";       font-weight:normal;  }
      attractions destination general:before {content: "";    border-top-width: 0pt; }
      attractions destination general introduction:before print_before(""); break;
      activities introduction:before print_before(""); break;
      event type:before { content: "Type: ";  font-weight:normal; }
      event from_date:before { content: "From: ";     font-weight:normal; }
      event to_date:before { content: "To: "; font-weight:normal; }
      event summary:before { content: "Summary ";     font-weight:normal; }
      condition type:before { content: "Type of condition: "; font-weight:normal; }*/
      case "book": print_before("Book"); break;
      case "periodical": print_before("Periodical"); break;
      case "radio_station": print_before("Radio"); break;
      case "address": print_before("Address: "); break;
      case "telfaxs": print_before("Telephone/Facsimiles: "); break;
      case "url": print_before("Url: "); break;
      case "review": print_before("Review"); break;
      case "admission": print_before("Admission"); break;
      case "keywords": print_before("Keywords"); break;
      case "adaptors": print_before("Adaptors"); break;
      case "mobile": print_before("Mobile telephony"); break;
      case "areacodes": print_before("Areacodes"); break;
      case "dailling_out": print_before("Dailing out: "); break;
      case "country_dialling_code": print_before("Country code: "); break;
//       government type:before print_before("Type of government: "); break;
      case "area_sqkm": print_before("Area (in square kilometers): "); break;
      case "latitude": print_before("Latitude: "); break;
      case "longditude": print_before("Longditude: "); break;
      case "population": print_before("Population: "); break;
      case "e-mail": print_before("E-mail: "); break;
      case "range": print_before("Range: "); break;
      case "creditcards": print_before("Creditcards accepted: "); break;
      /*hours time:before print_before("Hours: "); break;
      hours_variation time:before print_before("Hours (variation): "); break;*/
      case "variation": print_before("Variation: "); break;
      case "full_name": print_before("Full destination name: "); break;
      case "type": print_before("Type:"); break;
      case "severity": print_before("Severity:"); break;
      case "photographer": print_before("Photographer: "); break;

      case "humidity":print_before("Humidity (%): "); break;
      case "avg_hi_temp":print_before(" - Average high temperature: "); break;
      case "avg_low_temp":print_before(" - Average low temperature: "); break;
      case "avg_rainfall":print_before(" - Average rainfall: "); break;
      case "avg_sunshine":print_before(" - Average sunshine: "); break;
      case "am":print_before(" - PM: "); break;
      case "pm":print_before(" - AM: "); break;
      case "avg":print_before("Average: "); break;

      case "capital_city": print_before("Capital city"); break;

      case "image":
            print "<html:img alt=\"[img]\" src=\"$base_url/media/lp" .  $attrs["filename"] . "\"/>";
            break;
   }
   array_push($tagstack, $name);
}

?>