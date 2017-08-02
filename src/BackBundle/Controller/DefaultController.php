<?php

namespace BackBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;
use Ivory\GoogleMap\Map;
use Ivory\GoogleMap\Base\Coordinate;
use Ivory\GoogleMap\Base\Bound;
use Ivory\GoogleMap\MapTypeId;
use Ivory\GoogleMap\Overlay\Animation;
use Ivory\GoogleMap\Overlay\Icon;
use Ivory\GoogleMap\Overlay\Marker;
use Ivory\GoogleMap\Overlay\MarkerShape;
use Ivory\GoogleMap\Overlay\MarkerShapeType;
use Ivory\GoogleMap\Overlay\Symbol;
use Ivory\GoogleMap\Overlay\SymbolPath;
use Ivory\GoogleMap\Overlay\InfoWindow;
use Ivory\GoogleMap\Overlay\InfoWindowType;
use Ivory\GoogleMapBundle\Form\Type\PlaceAutocompleteType;
use Ivory\GoogleMap\Base\Size;
use Ivory\GoogleMap\Event\MouseEvent;
use Ivory\GoogleMap\Control\ControlPosition;
use Ivory\GoogleMap\Control\MapTypeControl;
use Ivory\GoogleMap\Control\MapTypeControlStyle;
use Ivory\GoogleMap\Control\RotateControl;
use Ivory\GoogleMap\Control\FullscreenControl;
use Ivory\GoogleMap\Control\ScaleControl;
use Ivory\GoogleMap\Control\ScaleControlStyle;
use Ivory\GoogleMap\Control\StreetViewControl;
use Ivory\GoogleMap\Control\ZoomControl;
use Ivory\GoogleMap\Control\ZoomControlStyle;
use Ivory\GoogleMap\Control\CustomControl;
use Ivory\GoogleMap\Event\Event;
use Ivory\GoogleMap\Place\Autocomplete;
use Ivory\GoogleMap\Layer\GeoJsonLayer;
use Ivory\GoogleMap\Layer\HeatmapLayer;
use Ivory\GoogleMap\Layer\KmlLayer;
use Ivory\GoogleMap\Overlay\Rectangle;
use Ivory\GoogleMap\Overlay\GroundOverlay;
use Ivory\GoogleMap\Overlay\MarkerClusterType;
use Ivory\GoogleMap\Helper\Builder\MapHelperBuilder;
use Ivory\GoogleMap\Helper\Builder\ApiHelperBuilder;
use Ivory\GoogleMap\Service\Direction\DirectionService;
use Http\Adapter\Guzzle6\Client;
use Http\Message\MessageFactory\GuzzleMessageFactory;
use Ivory\GoogleMap\Service\Serializer\SerializerBuilder;
use Ivory\GoogleMap\Service\Base\Location\AddressLocation;
use Ivory\GoogleMap\Service\Direction\Request\DirectionRequest;
use Ivory\GoogleMap\Service\Geocoder\Request\GeocoderAddressRequest;
use Ivory\GoogleMap\Service\Geocoder\Request\GeocoderRequestInterface;
use Ivory\GoogleMap\Service\Geocoder\GeocoderService;

class DefaultController extends Controller {

    public function indexAction() {

        //MAP
        $map = new Map();
        $map->setAutoZoom(false);
        $map->setCenter(new Coordinate(37.782552, -122.445370));
        $map->setMapOption('zoom', 17);
        //$map->setMapOption('mapTypeId', MapTypeId::TERRAIN);
        // $map->setBound(new Bound(new Coordinate(40.66, -74.200), new Coordinate(40.7808, -73.9772)));
        $map->getOverlayManager()->addMarker(new Marker(new Coordinate(37.7832, -122.445370)));
        $map->getOverlayManager()->addMarker(new Marker(new Coordinate(37.78292, -122.445374)));
        $map->getOverlayManager()->addMarker(new Marker(new Coordinate(37.7844, -122.445374)));
        $map->getOverlayManager()->addMarker(new Marker(new Coordinate(37.7831, -122.445374)));
        //CONTROL
        $rotateControl = new RotateControl(ControlPosition::TOP_RIGHT);
        $map->getControlManager()->setRotateControl($rotateControl);
        $fullscreenControl = new FullscreenControl(ControlPosition::TOP_LEFT);
        $map->getControlManager()->setFullscreenControl($fullscreenControl);
        $mapTypeControl = new MapTypeControl(
                [MapTypeId::ROADMAP, MapTypeId::HYBRID], ControlPosition::TOP_RIGHT, MapTypeControlStyle::DEFAULT_
        );
        $map->getControlManager()->setMapTypeControl($mapTypeControl);
        $scaleControl = new ScaleControl(
                ControlPosition::BOTTOM_LEFT, ScaleControlStyle::DEFAULT_
        );
        $map->getControlManager()->setScaleControl($scaleControl);
        $streetViewControl = new StreetViewControl(ControlPosition::TOP_LEFT);
        $map->getControlManager()->setStreetViewControl($streetViewControl);
        $zoomControl = new ZoomControl(
                ControlPosition::TOP_LEFT, ZoomControlStyle::DEFAULT_
        );
        $map->getControlManager()->setZoomControl($zoomControl);
        $control = <<<EOF
var control = document.createElement('div');
control.style.backgroundColor = 'grey';
control.style.border = '2px solid red';
control.style.marginBottom = '22px';
control.style.textAlign = 'center';
control.innerHTML = 'Control';

return control;
EOF;
        $customControl = new CustomControl(ControlPosition::TOP_CENTER, $control);
        $map->getControlManager()->addCustomControl($customControl);

        //EVENTS
        //LAYER
        //GEOJSON
        $geoJsonLayer = new GeoJsonLayer(
                'https://storage.googleapis.com/mapsdevsite/json/google.json', ['idPropertyName' => 'id']
        );
        $map->getLayerManager()->addGeoJsonLayer($geoJsonLayer);
        //Heatmap Layer
        $heatmapLayer = new HeatmapLayer(
                [
            new Coordinate(37.782551, -122.445368),
            new Coordinate(37.782745, -122.444586),
            new Coordinate(37.782842, -122.443688),
            new Coordinate(37.782919, -122.442815),
            new Coordinate(37.782992, -122.442112),
            new Coordinate(37.783100, -122.441461),
            new Coordinate(37.783206, -122.440829),
            new Coordinate(37.783273, -122.440324),
            new Coordinate(37.783316, -122.440023),
            new Coordinate(37.783357, -122.439794),
            new Coordinate(37.783371, -122.439687),
            new Coordinate(37.783368, -122.439666),
            new Coordinate(37.783383, -122.439594),
            new Coordinate(37.783508, -122.439525),
            new Coordinate(37.783842, -122.439591),
                // ...
                ], ['dissipating' => true]
        );
        $map->getLayerManager()->addHeatmapLayer($heatmapLayer);
        //KML LAYER 
        $kmlLayer = new KmlLayer(
                'http://www.domain.com/kml_layer.kml', ['suppressInfoWindows' => true]
        );
        $map->getLayerManager()->addKmlLayer($kmlLayer);
        //MARKER
        $marker = new Marker(
                new Coordinate(), Animation::DROP, new Icon(), new Symbol(SymbolPath::CIRCLE), new MarkerShape(MarkerShapeType::CIRCLE, [1.1, 2.1, 1.4]), ['clickable' => false]
        );
        $marker->setPosition(new Coordinate(37.782562, -122.445379));
        $map->getOverlayManager()->addMarker($marker);

        //Info Window

        $infoWindow = new InfoWindow('content', InfoWindowType::INFO_BOX, new Coordinate());
        $infoWindow->setContent('<p>ceci est un texte</p>');
        $infoWindow->setPosition(new Coordinate(37.782552, -122.445370));
        $infoWindow->setOpen(true);
        $infoWindow->setAutoOpen(true);
        $event = new Event(
                $marker->getVariable(), 'click', 'function(){alert("Marker clicked!");}', true
        );
        $infoWindow->setOpenEvent(MouseEvent::DBLCLICK);
        $map->getOverlayManager()->addInfoWindow($infoWindow);

        //rectangle
        $rectangle = new Rectangle(
                new Bound(
                new Coordinate(-1, -1), new Coordinate(1, 1)
                ), ['clickable' => false]
        );
        $map->getOverlayManager()->addRectangle($rectangle);
        //GroundOverlay
        $groundOverlay = new GroundOverlay(
                'https://www.lib.utexas.edu/maps/historical/newark_nj_1922.jpg', new Bound(new Coordinate(37.782552, -122.445370), new Coordinate(37.782552, -122.445370)), ['clickable' => false]
        );

        $map->getOverlayManager()->addGroundOverlay($groundOverlay);
        //CLustering
        $map->getOverlayManager()->getMarkerCluster()->setType(MarkerClusterType::MARKER_CLUSTERER);

        return $this->render('BackBundle:Default:index.html.twig', array(
                    'map' => $map
        ));
    }

}
