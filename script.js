// ===============================
// Student Profile
// ===============================
let studentProfile = {
    id: 1,
    username: "Dilshan",
    pointsBalance: 60,
    lastCheckIn: null
};


// ===============================
// Locations
// ===============================

// Indoor locations (QR)
const indoorLocations = ["library", "dining", "club_hub"];

// Outdoor locations (GPS)
const outdoorLocations = {
    gym: { lat: 7.201774, lon: 80.099194 },
    bus_stop: { lat: 6.9280, lon: 79.8600 },
    campus_gate: { lat: 6.9290, lon: 79.8620 }
};


// ===============================
// Check-in Handler
// ===============================
function handleCheckIn(locationType, locationValue) {

    // QR Check-in
    if (locationType === "QR") {

        if (!indoorLocations.includes(locationValue)) {

            document.getElementById("location-status").innerText =
                "Invalid QR code. No points awarded.";
            return;
        }

        processCheckIn(locationValue);
    }

    // GPS Check-in
    if (locationType === "GPS") {

        const options = {
            enableHighAccuracy: true,
            timeout: 10000,
            maximumAge: 0
        };

        navigator.geolocation.getCurrentPosition(
            checkLocation,
            showError,
            options
        );
    }
}


// ===============================
// Process Check-in
// ===============================
function processCheckIn(location) {

    if (studentProfile.lastCheckIn === location) {

        document.getElementById("location-status").innerText =
            "Duplicate check-in. No points awarded.";

        return;
    }

    const pointsAwarded = 10;

    studentProfile.pointsBalance += pointsAwarded;
    studentProfile.lastCheckIn = location;

    document.getElementById("poins-balance").innerText =
        studentProfile.pointsBalance;

    document.getElementById("location-status").innerText =
        `Check-in successful! +${pointsAwarded} points at ${location}.`;
}


// ===============================
// QR Check-in
// ===============================
function simulateQRCheckIn(locationValue) {

    if (indoorLocations.includes(locationValue)) {

        handleCheckIn("QR", locationValue);

    } else {

        document.getElementById("location-status").innerText =
            "Invalid QR code. No points awarded.";
    }
}


// ===============================
// GPS Check-in Button
// ===============================
function simulateGPSCheckIn() {

    if (!navigator.geolocation) {

        alert("Geolocation not supported");
        return;
    }

    handleCheckIn("GPS");
}


// ===============================
// Distance Calculator (Haversine)
// ===============================
function getDistance(lat1, lon1, lat2, lon2) {

    const R = 6371e3; // meters

    const φ1 = lat1 * Math.PI / 180;
    const φ2 = lat2 * Math.PI / 180;

    const Δφ = (lat2 - lat1) * Math.PI / 180;
    const Δλ = (lon2 - lon1) * Math.PI / 180;

    const a =
        Math.sin(Δφ / 2) * Math.sin(Δφ / 2) +
        Math.cos(φ1) * Math.cos(φ2) *
        Math.sin(Δλ / 2) * Math.sin(Δλ / 2);

    const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));

    return R * c;
}


// ===============================
// GPS Location Check
// ===============================
function checkLocation(position) {

    const lat = position.coords.latitude;
    const lon = position.coords.longitude;

    let matchedLocation = null;

    for (const [name, coords] of Object.entries(outdoorLocations)) {

        const distance = getDistance(
            lat, lon,
            coords.lat, coords.lon
        );

        // Allow 50 meters
        if (distance < 50) {
            matchedLocation = name;
            break;
        }
    }

    if (matchedLocation) {

        processCheckIn(matchedLocation);

    } else {

        document.getElementById("location-status").innerText =
            "You are too far from campus locations.";
    }
}


// ===============================
// GPS Error Handler
// ===============================
function showError(error) {

    let msg = "";

    switch (error.code) {

        case error.PERMISSION_DENIED:
            msg = "Location permission denied.";
            break;

        case error.POSITION_UNAVAILABLE:
            msg = "Location unavailable.";
            break;

        case error.TIMEOUT:
            msg = "GPS timeout.";
            break;

        default:
            msg = "Unknown GPS error.";
    }

    document.getElementById("location-status").innerText = msg;
}


// ===============================
// Google Maps
// ===============================

let map;
let userMarker;


// Initialize Map (called by Google API)
function initMap() {

    const center = {
        lat: outdoorLocations.gym.lat,
        lng: outdoorLocations.gym.lon
    };

    map = new google.maps.Map(
        document.getElementById("map"),
        {
            zoom: 16,
            center: center
        }
    );

    // Campus Markers
    for (const [name, coords] of Object.entries(outdoorLocations)) {

        new google.maps.Marker({
            position: {
                lat: coords.lat,
                lng: coords.lon
            },
            map: map,
            title: name
        });
    }

    trackUserLocation();
}


// ===============================
// Live User Tracking
// ===============================
function trackUserLocation() {

    if (!navigator.geolocation) return;

    navigator.geolocation.watchPosition(

        function (pos) {

            const userPos = {
                lat: pos.coords.latitude,
                lng: pos.coords.longitude
            };

            if (!userMarker) {

                userMarker = new google.maps.Marker({

                    position: userPos,
                    map: map,
                    icon: {
                        url: "https://maps.google.com/mapfiles/ms/icons/blue-dot.png"
                    },
                    title: "You"
                });

            } else {

                userMarker.setPosition(userPos);
            }

            map.setCenter(userPos);

        },

        showError,

        {
            enableHighAccuracy: true
        }
    );
}
