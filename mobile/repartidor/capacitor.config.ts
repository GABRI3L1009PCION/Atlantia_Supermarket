import type { CapacitorConfig } from '@capacitor/cli';

const config: CapacitorConfig = {
  appId: 'com.atlantia.delivery.repartidor',
  appName: 'Atlantia Repartidor',
  webDir: 'www',
  bundledWebRuntime: false,
  server: {
    androidScheme: 'http',
    cleartext: true
  },
  plugins: {
    LocalNotifications: {
      smallIcon: 'ic_stat_atlantia',
      iconColor: '#8b0832',
      sound: 'pedido.wav'
    },
    PushNotifications: {
      presentationOptions: ['badge', 'sound', 'alert']
    },
    Geolocation: {
      permissions: ['location', 'coarseLocation']
    }
  }
};

export default config;
