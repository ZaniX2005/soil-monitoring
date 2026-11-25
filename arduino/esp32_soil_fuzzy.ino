/*
  ESP32 Soil Monitoring + Fuzzy Tsukamoto
  Sensor: LM393 Soil Moisture Module
  Wiring:
    VCC -> 3V3
    GND -> GND
    AO  -> GPIO 34
    DO  -> tidak digunakan

  Ganti WIFI_SSID, WIFI_PASSWORD, SERVER_URL dan API_KEY.
*/

#include <WiFi.h>
#include <HTTPClient.h>

#define SOIL_PIN 34

const char* WIFI_SSID = "NAMA_WIFI";
const char* WIFI_PASSWORD = "PASSWORD_WIFI";

const char* SERVER_URL = "http://IP-KOMPUTER/soil-monitoring/api/sensor.php";
const char* DEVICE_KEY = "ESP32-SOIL-001";
const char* API_KEY = "ESP32-SOIL-001";

int nilaiKering = 3500;
int nilaiBasah  = 1500;

float fuzzyKering(float x) {
  if (x <= 30) return 1;
  if (x >= 50) return 0;
  return (50 - x) / 20.0;
}

float fuzzySedang(float x) {
  if (x <= 30 || x >= 70) return 0;
  if (x <= 50) return (x - 30) / 20.0;
  return (70 - x) / 20.0;
}

float fuzzyBasah(float x) {
  if (x <= 50) return 0;
  if (x >= 70) return 1;
  return (x - 50) / 20.0;
}

void setup() {
  Serial.begin(115200);
  analogReadResolution(12);

  WiFi.begin(WIFI_SSID, WIFI_PASSWORD);
  Serial.print("Menghubungkan WiFi");
  while (WiFi.status() != WL_CONNECTED) {
    delay(500);
    Serial.print(".");
  }
  Serial.println();
  Serial.print("WiFi OK. IP ESP32: ");
  Serial.println(WiFi.localIP());
}

void loop() {
  int adc = analogRead(SOIL_PIN);

  float moisture = map(adc, nilaiKering, nilaiBasah, 0, 100);
  moisture = constrain(moisture, 0, 100);

  float kering = fuzzyKering(moisture);
  float sedang = fuzzySedang(moisture);
  float basah  = fuzzyBasah(moisture);

  // Rule:
  // R1 IF Kering THEN Lama
  // R2 IF Sedang THEN Lama
  // R3 IF Basah THEN Sebentar
  float a1 = kering;
  float a2 = sedang;
  float a3 = basah;

  float z1 = 5 + (a1 * 5); // Lama: 5..10 detik
  float z2 = 5 + (a2 * 5);
  float z3 = 5 - (a3 * 5); // Sebentar: 5..0 detik

  float denominator = a1 + a2 + a3;
  float watering = denominator == 0 ? 0 : ((a1*z1)+(a2*z2)+(a3*z3))/denominator;

  String status;
  if (moisture < 30) status = "Kering";
  else if (moisture < 70) status = "Sedang";
  else status = "Basah";

  Serial.printf("ADC=%d | Kelembapan=%.1f%% | Fuzzy=%s | Siram=%.1fs\n",
                adc, moisture, status.c_str(), watering);

  if (WiFi.status() == WL_CONNECTED) {
    HTTPClient http;
    http.begin(SERVER_URL);
    http.addHeader("Content-Type", "application/json");
    http.addHeader("X-API-KEY", API_KEY);

    String body = "{";
    body += "\"device_key\":\"" + String(DEVICE_KEY) + "\",";
    body += "\"api_key\":\"" + String(API_KEY) + "\",";
    body += "\"adc_value\":" + String(adc) + ",";
    body += "\"moisture_percent\":" + String(moisture, 2) + ",";
    body += "\"fuzzy_status\":\"" + status + "\",";
    body += "\"watering_seconds\":" + String(watering, 2);
    body += "}";

    int code = http.POST(body);
    Serial.print("HTTP: ");
    Serial.println(code);
    if (code > 0) Serial.println(http.getString());
    http.end();
  }

  delay(5000);
}
