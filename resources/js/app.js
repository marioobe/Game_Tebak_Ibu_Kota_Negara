import QRCode from 'qrcode';
import L from 'leaflet';

window.QRCode = QRCode;
window.L = L;

// Global map helper for Game Tebak Ibu Kota
window.createCountryMap = function (containerId) {
    const container = document.getElementById(containerId);
    if (!container) return null;

    // Reset container if already initialized
    if (container._leaflet_id) {
        container._leaflet_id = null;
        container.innerHTML = '';
    }

    const map = L.map(containerId, {
        center: [20, 0],
        zoom: 2,
        minZoom: 1,
        maxZoom: 8,
        zoomControl: false,
        attributionControl: false,
        dragging: !L.Browser.mobile,
        touchZoom: true,
        scrollWheelZoom: false,
    });

    let worldGeoJsonData = null;
    let baseLayer = null;
    let activeHighlightLayer = null;
    let capitalMarker = null;

    // Fetch local GeoJSON (cached in memory)
    const loadData = async () => {
        if (!window.__worldGeoJson) {
            const res = await fetch('/data/world-boundaries.json');
            window.__worldGeoJson = await res.json();
        }
        worldGeoJsonData = window.__worldGeoJson;

        // Render base world layer
        baseLayer = L.geoJSON(worldGeoJsonData, {
            style: {
                color: '#334155',
                weight: 1,
                fillColor: '#0f172a',
                fillOpacity: 0.9,
            }
        }).addTo(map);

        return worldGeoJsonData;
    };

    const dataPromise = loadData();

    return {
        map: map,
        focusCountry: async function (countryNameId, revealCapital = false, capitalName = '') {
            await dataPromise;

            // Remove existing highlight & marker
            if (activeHighlightLayer) {
                map.removeLayer(activeHighlightLayer);
                activeHighlightLayer = null;
            }
            if (capitalMarker) {
                map.removeLayer(capitalMarker);
                capitalMarker = null;
            }

            if (!worldGeoJsonData || !countryNameId) return;

            const targetFeature = worldGeoJsonData.features.find(
                f => f.properties && f.properties.name_id === countryNameId
            );

            if (!targetFeature) return;

            // Draw highlighted boundary
            activeHighlightLayer = L.geoJSON(targetFeature, {
                style: {
                    color: '#a5b4fc', // indigo-300 border
                    weight: 2.5,
                    fillColor: '#4f46e5', // indigo-600 fill
                    fillOpacity: 0.75,
                }
            }).addTo(map);

            // Smooth fly/zoom to country boundary
            try {
                const bounds = activeHighlightLayer.getBounds();
                map.fitBounds(bounds, {
                    padding: [25, 25],
                    maxZoom: 6,
                    animate: true,
                    duration: 0.8,
                });
            } catch (e) {
                console.error('Fit bounds error', e);
            }

            // Reveal capital city pin if requested
            if (revealCapital && targetFeature.properties.capital_coords) {
                const [lat, lng] = targetFeature.properties.capital_coords;
                const pinIcon = L.divIcon({
                    className: 'custom-map-pin',
                    html: `
                        <div class="relative flex items-center justify-center">
                            <span class="absolute w-6 h-6 rounded-full bg-emerald-400 animate-ping opacity-75"></span>
                            <div class="w-7 h-7 rounded-full bg-emerald-500 border-2 border-white shadow-xl flex items-center justify-center text-xs text-white font-bold">
                                📍
                            </div>
                        </div>
                    `,
                    iconSize: [28, 28],
                    iconAnchor: [14, 14],
                });

                capitalMarker = L.marker([lat, lng], { icon: pinIcon }).addTo(map);
                if (capitalName) {
                    capitalMarker.bindTooltip(capitalName, {
                        permanent: true,
                        direction: 'top',
                        className: 'map-capital-tooltip'
                    }).openTooltip();
                }
            }
        },
        invalidateSize: function () {
            map.invalidateSize();
        }
    };
};
