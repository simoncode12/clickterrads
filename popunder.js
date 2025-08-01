/**
 * ClickTerra Popunder Script
 * Version 1.0.0
 * Usage: ClickTerraPopunder.init({ zone_id: YOUR_ZONE_ID, frequency: 24 })
 */
(function() {
    'use strict';
    
    // Mendefinisikan objek global ClickTerraPopunder
    window.ClickTerraPopunder = {
        // Konfigurasi default
        config: {
            zone_id: null,
            frequency: 24, // jam
            debug: false,
            cookieName: 'ct_popunder_shown'
        },
        
        /**
         * Inisialisasi Popunder
         * @param {Object} options - Konfigurasi popunder
         */
        init: function(options) {
            // Gabungkan opsi yang diberikan dengan konfigurasi default
            this.config = Object.assign({}, this.config, options || {});
            
            if (!this.config.zone_id) {
                this.log('Error: zone_id diperlukan!');
                return;
            }
            
            // Cek apakah browser mendukung localStorage
            if (!this.storageAvailable('localStorage')) {
                this.log('Warning: localStorage tidak tersedia, menggunakan cookie sebagai fallback.');
            }
            
            // Cek apakah pengguna sudah melihat popunder dalam frekuensi tertentu
            if (!this.wasRecentlyShown()) {
                // Tambahkan event listener untuk memicu popunder
                this.attachEvents();
                this.log('Popunder siap dengan zone_id: ' + this.config.zone_id);
            } else {
                this.log('Popunder tidak ditampilkan karena frekuensi membatasi.');
            }
        },
        
        /**
         * Pasang event listeners untuk memicu popunder
         */
        attachEvents: function() {
            var self = this;
            
            // Fungsi untuk memicu popunder
            var triggerPopunder = function() {
                self.showPopunder();
                // Hapus event listener setelah popunder muncul satu kali
                document.removeEventListener('click', triggerPopunder);
                document.removeEventListener('touchstart', triggerPopunder);
            };
            
            // Pasang event listener pada klik dan touch
            document.addEventListener('click', triggerPopunder);
            document.addEventListener('touchstart', triggerPopunder);
        },
        
        /**
         * Tampilkan popunder dan simpan waktu terakhir ditampilkan
         */
        showPopunder: function() {
            var self = this;
            var baseUrl = this.getBaseUrl();
            var popunderUrl = baseUrl + '/popunder.php?zone_id=' + this.config.zone_id;
            
            // Simpan waktu saat ini sebagai waktu terakhir popunder ditampilkan
            this.setLastShown();
            
            // Teknik untuk mengatasi pemblokir popunder
            var popWin = null;
            try {
                // Chrome & Firefox Workaround
                // Buka window kemudian fokus ke parent window
                popWin = window.open(popunderUrl, '_blank');
                if (popWin) {
                    popWin.blur();
                    window.focus();
                    
                    // Khusus untuk Safari
                    if (navigator.userAgent.indexOf('Safari') > -1 && navigator.userAgent.indexOf('Chrome') === -1) {
                        window.addEventListener('blur', function() {
                            setTimeout(function() {
                                window.focus();
                            }, 500);
                        }, {once: true});
                    }
                    
                    this.log('Popunder berhasil dibuka');
                } else {
                    this.log('Popunder diblokir oleh browser');
                }
            } catch (e) {
                this.log('Error saat membuka popunder: ' + e.message);
            }
        },
        
        /**
         * Cek apakah popunder sudah ditampilkan sebelumnya dalam periode frekuensi
         * @return {Boolean} True jika sudah ditampilkan dalam periode frekuensi
         */
        wasRecentlyShown: function() {
            var lastShown = this.getLastShown();
            
            if (!lastShown) {
                return false;
            }
            
            var now = new Date().getTime();
            var hoursSinceLastShown = (now - lastShown) / (1000 * 60 * 60);
            
            return hoursSinceLastShown < this.config.frequency;
        },
        
        /**
         * Simpan waktu terakhir popunder ditampilkan
         */
        setLastShown: function() {
            var now = new Date().getTime();
            
            if (this.storageAvailable('localStorage')) {
                localStorage.setItem(this.config.cookieName, now);
            } else {
                // Fallback ke cookie
                var expiration = new Date();
                expiration.setHours(expiration.getHours() + this.config.frequency);
                document.cookie = this.config.cookieName + '=' + now + ';expires=' + expiration.toUTCString() + ';path=/';
            }
        },
        
        /**
         * Ambil waktu terakhir popunder ditampilkan
         * @return {Number} Timestamp terakhir popunder ditampilkan
         */
        getLastShown: function() {
            if (this.storageAvailable('localStorage')) {
                return localStorage.getItem(this.config.cookieName);
            } else {
                // Fallback ke cookie
                var nameEQ = this.config.cookieName + "=";
                var ca = document.cookie.split(';');
                for(var i=0; i < ca.length; i++) {
                    var c = ca[i];
                    while (c.charAt(0) == ' ') c = c.substring(1, c.length);
                    if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length, c.length);
                }
            }
            return null;
        },
        
        /**
         * Cek apakah browser mendukung storage tertentu
         * @param {String} type - Tipe storage ('localStorage', 'sessionStorage')
         * @return {Boolean} True jika storage tersedia
         */
        storageAvailable: function(type) {
            try {
                var storage = window[type],
                    x = '__storage_test__';
                storage.setItem(x, x);
                storage.removeItem(x);
                return true;
            } catch(e) {
                return false;
            }
        },
        
        /**
         * Dapatkan base URL dari skrip saat ini
         * @return {String} Base URL
         */
        getBaseUrl: function() {
            var scripts = document.getElementsByTagName('script');
            for (var i = 0; i < scripts.length; i++) {
                var src = scripts[i].src;
                if (src.indexOf('popunder.js') > -1) {
                    return src.substring(0, src.lastIndexOf('/'));
                }
            }
            // Fallback jika tidak bisa mendeteksi base URL
            return window.location.protocol + '//' + window.location.host;
        },
        
        /**
         * Log pesan jika mode debug aktif
         * @param {String} message - Pesan untuk dilog
         */
        log: function(message) {
            if (this.config.debug) {
                console.log('[ClickTerraPopunder] ' + message);
            }
        }
    };
})();