import Alpine from 'alpinejs';
import L from 'leaflet';

window.Alpine = Alpine;
window.L = L;

const installButton = document.querySelector('[data-install-app]');
const pwaStatus = document.querySelector('[data-pwa-status]');
let installPrompt = null;

window.addEventListener('beforeinstallprompt', (event) => {
    event.preventDefault();
    installPrompt = event;
    if (installButton) {
        installButton.hidden = false;
    }
});

installButton?.addEventListener('click', async () => {
    if (!installPrompt) {
        return;
    }

    await installPrompt.prompt();
    const choice = await installPrompt.userChoice;
    pwaStatus.textContent = choice.outcome === 'accepted' ? 'App installed.' : 'Install dismissed.';
    installPrompt = null;
    installButton.hidden = true;
});

window.addEventListener('appinstalled', () => {
    if (installButton) {
        installButton.hidden = true;
    }
    if (pwaStatus) {
        pwaStatus.textContent = 'App installed.';
    }
});

if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js').catch(() => {
        if (pwaStatus) {
            pwaStatus.textContent = 'Offline support could not be started.';
        }
    });
}

window.riderTracker = (config) => ({
    ...config,
    activeRide: false,
    lastLocation: null,
    message: '',
    timer: null,
    activityTimer: null,
    wakeLock: null,

    async init() {
        window.addEventListener('online', () => {
            this.message = '';
            this.schedule(0);
        });
        window.addEventListener('offline', () => {
            this.message = this.messages.offline;
        });
        document.addEventListener('visibilitychange', () => {
            if (document.visibilityState === 'visible' && this.online) {
                this.acquireWakeLock();
                this.schedule(0);
            }
        });

        if (!this.online) {
            this.message = this.messages.offline;
            return;
        }

        this.acquireWakeLock();
        this.activityTimer = window.setInterval(() => this.checkActivity(), 5000);
        this.checkActivity();
        this.schedule(0);
    },

    async acquireWakeLock() {
        if (!navigator.wakeLock || document.visibilityState !== 'visible') {
            return;
        }

        try {
            this.wakeLock = await navigator.wakeLock.request('screen');
        } catch {
            this.wakeLock = null;
        }
    },

    async checkActivity() {
        try {
            const response = await fetch(this.activeUrl, { headers: { Accept: 'application/json' } });

            if (!response.ok) {
                throw new Error('Activity check failed');
            }

            const data = await response.json();
            const wasActive = this.activeRide;
            this.activeRide = Boolean(data.active_ride);

            if (this.activeRide && !wasActive) {
                this.schedule(0);
            }
        } catch {
            this.message = this.messages.networkError;
        }
    },

    schedule(delaySeconds) {
        window.clearTimeout(this.timer);

        if (!this.online) {
            return;
        }

        this.timer = window.setTimeout(
            () => this.updateFromGps(),
            Math.max(1, delaySeconds) * 1000,
        );
    },

    updateFromGps() {
        if (!navigator.onLine) {
            this.message = this.messages.offline;
            this.schedule(this.idleSeconds);
            return;
        }

        if (!navigator.geolocation) {
            this.message = this.messages.gpsError;
            this.schedule(this.idleSeconds);
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => this.sendLocation(position.coords.latitude, position.coords.longitude),
            (error) => {
                this.message = error.code === error.PERMISSION_DENIED
                    ? this.messages.permission
                    : this.messages.gpsError;
                this.schedule(this.activeRide ? this.activeSeconds : this.idleSeconds);
            },
            { enableHighAccuracy: true, maximumAge: 0, timeout: 15000 },
        );
    },

    async sendLocation(lat, lng) {
        if (!this.activeRide && this.lastLocation && this.distanceMeters(this.lastLocation, { lat, lng }) <= this.minMoveMeters) {
            this.message = this.messages.waiting;
            this.schedule(this.idleSeconds);
            return;
        }

        try {
            const response = await fetch(this.locationUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
                body: JSON.stringify({ lat, lng }),
            });

            if (!response.ok) {
                throw new Error('Location update failed');
            }

            this.lastLocation = { lat, lng };
            this.message = this.messages.updated;
        } catch {
            this.message = this.messages.networkError;
        }

        this.schedule(this.activeRide ? this.activeSeconds : this.idleSeconds);
    },

    distanceMeters(from, to) {
        const toRadians = (degrees) => degrees * Math.PI / 180;
        const latitudeDelta = toRadians(to.lat - from.lat);
        const longitudeDelta = toRadians(to.lng - from.lng);
        const value = Math.sin(latitudeDelta / 2) ** 2
            + Math.cos(toRadians(from.lat)) * Math.cos(toRadians(to.lat))
            * Math.sin(longitudeDelta / 2) ** 2;

        return 6371000 * 2 * Math.atan2(Math.sqrt(value), Math.sqrt(1 - value));
    },
});
window.passengerMap = (config) => ({
    ...config,
    map: null,
    pickupMarker: null,
    dropoffMarker: null,
    pickup: null,
    dropoff: null,
    fare: null,
    distance: null,
    message: config.messages.select,
    locationMessage: '',
    estimateTimer: null,

    get canBook() {
        return Boolean(this.pickup && this.dropoff && this.fare);
    },

    init() {
        this.map = L.map('ride-map').setView([33.7667, 72.3592], 13);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(this.map);
        this.map.on('click', (event) => {
            this.setPoint(this.pickup ? 'dropoff' : 'pickup', event.latlng.lat, event.latlng.lng);
        });
    },

    setPoint(kind, lat, lng) {
        const point = { lat, lng };
        const markerName = kind === 'pickup' ? 'pickupMarker' : 'dropoffMarker';
        const inputPrefix = kind === 'pickup' ? 'pickup' : 'dropoff';

        this[kind] = point;
        if (this[markerName]) {
            this[markerName].setLatLng([lat, lng]);
        } else {
            this[markerName] = L.marker([lat, lng], { draggable: true }).addTo(this.map);
            this[markerName].on('dragend', (event) => {
                const position = event.target.getLatLng();
                this.setPoint(kind, position.lat, position.lng);
            });
        }

        this.$refs[`${inputPrefix}Lat`].value = lat.toFixed(8);
        this.$refs[`${inputPrefix}Lng`].value = lng.toFixed(8);
        if (!this.$refs[`${inputPrefix}Address`].value) {
            this.$refs[`${inputPrefix}Address`].value = kind === 'pickup' ? 'Selected pickup point' : 'Selected drop-off point';
        }

        this.estimateFare();
    },

    resetPoints() {
        this.pickup = null;
        this.dropoff = null;
        this.fare = null;
        this.distance = null;
        this.message = this.messages.select;
        this.$refs.pickupLat.value = '';
        this.$refs.pickupLng.value = '';
        this.$refs.dropoffLat.value = '';
        this.$refs.dropoffLng.value = '';
        this.$refs.pickupAddress.value = '';
        this.$refs.dropoffAddress.value = '';
        if (this.pickupMarker) {
            this.map.removeLayer(this.pickupMarker);
            this.pickupMarker = null;
        }
        if (this.dropoffMarker) {
            this.map.removeLayer(this.dropoffMarker);
            this.dropoffMarker = null;
        }
    },

    useCurrentLocation() {
        if (!navigator.geolocation) {
            this.locationMessage = this.messages.gpsError;
            return;
        }

        navigator.geolocation.getCurrentPosition(
            (position) => {
                this.setPoint('pickup', position.coords.latitude, position.coords.longitude);
                this.$refs.pickupAddress.value = 'My current location';
                this.map.setView([position.coords.latitude, position.coords.longitude], 15);
                this.locationMessage = '';
            },
            () => {
                this.locationMessage = this.messages.gpsError;
            },
            { enableHighAccuracy: true, timeout: 15000 },
        );
    },

    estimateFare() {
        window.clearTimeout(this.estimateTimer);

        if (!this.pickup || !this.dropoff) {
            this.fare = null;
            this.distance = null;
            this.message = this.messages.select;
            return;
        }

        this.message = this.messages.estimating;
        this.estimateTimer = window.setTimeout(async () => {
            try {
                const response = await fetch(this.estimateUrl, {
                    method: 'POST',
                    headers: {
                        Accept: 'application/json',
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        pickup_lat: this.pickup.lat,
                        pickup_lng: this.pickup.lng,
                        dropoff_lat: this.dropoff.lat,
                        dropoff_lng: this.dropoff.lng,
                    }),
                });

                if (!response.ok) {
                    throw new Error('Fare estimation failed');
                }

                const result = await response.json();
                this.fare = result.estimated_fare;
                this.distance = result.distance_km;
                this.message = '';
            } catch {
                this.fare = null;
                this.distance = null;
                this.message = this.messages.estimateError;
            }
        }, 250);
    },
});
window.rideTracking = (config) => ({
    ...config,
    map: null,
    riderMarker: null,
    timer: null,
    get statusLabel() {
        if (this.noRidersFound) {
            return 'No riders accepted in time';
        }

        return {
            searching: 'Searching for a rider',
            accepted: 'Rider accepted your trip',
            arrived: 'Your rider has arrived',
            in_progress: 'Ride in progress',
            completed: 'Ride completed',
            cancelled: 'Ride cancelled',
        }[this.status] ?? this.status;
    },
    get stepIndex() {
        return ['searching', 'accepted', 'arrived', 'in_progress', 'completed'].indexOf(this.status);
    },

    init() {
        this.status = this.initialStatus;
        this.map = L.map('live-rider-map').setView(this.pickup, 14);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(this.map);
        L.marker(this.pickup).addTo(this.map);
        this.updateRiderMarker();
        this.poll();
    },

    async poll() {
        try {
            const response = await fetch(this.statusUrl, { headers: { Accept: 'application/json' } });

            if (!response.ok) {
                throw new Error('Ride update failed');
            }

            const data = await response.json();
            this.status = data.status;
            this.noRidersFound = Boolean(data.no_riders_found);
            this.rider = data.rider;
            this.updateRiderMarker();
        } catch {
            this.message = 'Ride updates are temporarily unavailable.';
        }

        if (!['completed', 'cancelled'].includes(this.status)) {
            this.timer = window.setTimeout(() => this.poll(), 10000);
        }
    },

    updateRiderMarker() {
        if (!this.rider?.lat || !this.rider?.lng) {
            return;
        }

        const point = [Number(this.rider.lat), Number(this.rider.lng)];
        if (this.riderMarker) {
            this.riderMarker.setLatLng(point);
        } else {
            this.riderMarker = L.marker(point).addTo(this.map);
        }
        this.map.setView(point, Math.max(this.map.getZoom(), 14));
    },
});
window.rideRequestPoller = (config) => ({
    ...config,
    requests: [],
    knownIds: new Set(),
    alarmEnabled: false,
    audioContext: null,
    accepting: null,
    message: '',
    timer: null,

    init() {
        this.poll();
    },

    async enableAlerts() {
        const AudioContext = window.AudioContext || window.webkitAudioContext;

        if (!AudioContext) {
            this.message = 'Sound alerts are not supported in this browser. Keep this panel open to see new requests.';
            return;
        }

        this.audioContext ??= new AudioContext();
        await this.audioContext.resume();
        this.alarmEnabled = true;
        this.playAlert();
    },

    playAlert() {
        if (!this.alarmEnabled || !this.audioContext) {
            return;
        }

        const startAt = this.audioContext.currentTime;
        for (let index = 0; index < 3; index += 1) {
            const oscillator = this.audioContext.createOscillator();
            const gain = this.audioContext.createGain();
            const start = startAt + index * 0.28;
            oscillator.type = 'sine';
            oscillator.frequency.setValueAtTime(880, start);
            gain.gain.setValueAtTime(0.0001, start);
            gain.gain.exponentialRampToValueAtTime(0.3, start + 0.03);
            gain.gain.exponentialRampToValueAtTime(0.0001, start + 0.2);
            oscillator.connect(gain);
            gain.connect(this.audioContext.destination);
            oscillator.start(start);
            oscillator.stop(start + 0.21);
        }
    },

    async poll() {
        try {
            const response = await fetch(this.requestsUrl, { headers: { Accept: 'application/json' } });

            if (!response.ok) {
                throw new Error('Request polling failed');
            }

            const data = await response.json();
            this.requests = data.requests;
            const currentIds = new Set(this.requests.map((request) => request.id));
            const hasNewRequest = [...currentIds].some((id) => !this.knownIds.has(id));

            if (hasNewRequest) {
                this.playAlert();
                if (navigator.vibrate) {
                    navigator.vibrate([300, 120, 300]);
                }
                this.message = 'New ride request nearby.';
            } else if (this.requests.length === 0) {
                this.message = '';
            }

            this.knownIds = currentIds;
        } catch {
            this.message = 'Ride requests are temporarily unavailable. Retrying shortly.';
        }

        this.timer = window.setTimeout(() => this.poll(), this.pollSeconds * 1000);
    },

    async accept(id) {
        this.accepting = id;
        this.message = '';

        try {
            const response = await fetch(this.acceptUrl.replace('__RIDE__', id), {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                },
            });
            const data = await response.json();

            if (!response.ok) {
                throw new Error(data.message || data.errors?.ride?.[0] || 'This ride request is no longer available.');
            }

            window.location.assign(data.redirect);
        } catch (error) {
            this.message = error.message;
            this.requests = this.requests.filter((request) => request.id !== id);
        } finally {
            this.accepting = null;
        }
    },
});
window.adminAlarm = (config) => ({
    ...config,
    deposits: [],
    kyc: [],
    knownDepositIds: new Set(),
    knownKycIds: new Set(),
    pendingCount: 0,
    notifications: [],
    alarmEnabled: false,
    message: '',
    timer: null,

    init() {
        this.poll();
    },

    async enableAlarm() {
        try {
            this.$refs.alarm.currentTime = 0;
            await this.$refs.alarm.play();
            this.alarmEnabled = true;
        } catch {
            this.message = 'Sound could not be enabled. Check your browser audio settings and try again.';
        }
    },

    playAlarm() {
        if (!this.alarmEnabled) {
            return;
        }

        this.$refs.alarm.currentTime = 0;
        this.$refs.alarm.play().catch(() => {
            this.alarmEnabled = false;
            this.message = 'Alarm OFF: browser audio permission is required.';
        });
    },

    notify(message) {
        const notice = { id: `${Date.now()}-${Math.random()}`, message };
        this.notifications.unshift(notice);
        this.notifications = this.notifications.slice(0, 3);
        window.setTimeout(() => {
            this.notifications = this.notifications.filter((item) => item.id !== notice.id);
        }, 12000);
        this.playAlarm();
    },

    async poll() {
        try {
            const response = await fetch(this.pendingUrl, { headers: { Accept: 'application/json' } });

            if (!response.ok) {
                throw new Error('Pending items could not be loaded');
            }

            const data = await response.json();
            this.deposits = data.deposits;
            this.kyc = data.kyc;
            this.pendingCount = this.deposits.length + this.kyc.length;

            for (const deposit of this.deposits) {
                if (!this.knownDepositIds.has(deposit.id)) {
                    this.notify(this.messages.newDeposit
                        .replace(':rider', deposit.rider_name)
                        .replace(':amount', deposit.amount)
                        .replace(':tid', deposit.trx_id));
                }
            }

            for (const application of this.kyc) {
                if (!this.knownKycIds.has(application.id)) {
                    this.notify(this.messages.newKyc.replace(':rider', application.rider_name));
                }
            }

            this.knownDepositIds = new Set(this.deposits.map((deposit) => deposit.id));
            this.knownKycIds = new Set(this.kyc.map((application) => application.id));
            this.message = '';
        } catch {
            this.message = this.messages.network;
        }

        this.timer = window.setTimeout(() => this.poll(), this.pollSeconds * 1000);
    },
});
window.adminLiveMap = (config) => ({
    ...config,
    map: null,
    riderLayer: null,
    rideLayer: null,
    riders: [],
    rides: [],
    message: '',
    timer: null,

    init() {
        this.map = L.map('admin-live-map').setView([33.7667, 72.3592], 12);
        L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
            maxZoom: 19,
            attribution: '&copy; OpenStreetMap contributors',
        }).addTo(this.map);
        this.riderLayer = L.layerGroup().addTo(this.map);
        this.rideLayer = L.layerGroup().addTo(this.map);
        this.poll();
    },

    popup(title, detail) {
        const content = document.createElement('div');
        const heading = document.createElement('strong');
        const description = document.createElement('p');
        heading.textContent = title;
        description.textContent = detail;
        content.append(heading, description);
        return content;
    },

    async poll() {
        try {
            const response = await fetch(this.snapshotUrl, { headers: { Accept: 'application/json' } });

            if (!response.ok) {
                throw new Error('Map snapshot could not be loaded');
            }

            const data = await response.json();
            this.riders = data.riders;
            this.rides = data.rides;
            this.renderMarkers();
            this.message = '';
        } catch {
            this.message = 'Live map is temporarily unavailable. Retrying shortly.';
        }

        this.timer = window.setTimeout(() => this.poll(), 10000);
    },

    renderMarkers() {
        this.riderLayer.clearLayers();
        this.rideLayer.clearLayers();

        for (const rider of this.riders) {
            L.marker([Number(rider.lat), Number(rider.lng)])
                .bindPopup(this.popup(`Rider: ${rider.name}`, `${rider.phone} · ${rider.bike_number || ''}`))
                .addTo(this.riderLayer);
        }

        for (const ride of this.rides) {
            L.marker(ride.pickup.map(Number))
                .bindPopup(this.popup(`Ride #${ride.id} · ${ride.status}`, `${ride.pickup_address} → ${ride.dropoff_address}`))
                .addTo(this.rideLayer);
            L.marker(ride.dropoff.map(Number), { opacity: 0.6 })
                .bindPopup(this.popup(`Drop-off · Ride #${ride.id}`, ride.dropoff_address))
                .addTo(this.rideLayer);
            L.polyline([ride.pickup.map(Number), ride.dropoff.map(Number)], { color: '#047857', weight: 3, opacity: 0.65 })
                .addTo(this.rideLayer);
        }
    },
});

Alpine.start();
