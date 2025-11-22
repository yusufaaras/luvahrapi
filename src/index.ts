const express = require("express");
const dotenv = require("dotenv");
dotenv.config();

const mongoose = require("mongoose");
const path = require("path");
const cors = require("cors");
const morgan = require("morgan");
const adminModule = require("./routes/admin");
const adminRoutes = adminModule && adminModule.default ? adminModule.default : adminModule;

// cv route'u require ile alıyoruz; export default kullanıldıysa default altındaki değeri alın
const cvModule = require("./routes/cv");
const cvRoutes = cvModule && cvModule.default ? cvModule.default : cvModule;

const app = express();
const PORT = process.env.PORT ? parseInt(process.env.PORT) : 5000;

app.use(cors());
app.use(morgan("tiny"));

// health-check
app.get("/health", (_req, res) => res.json({ ok: true }));

// API router
app.use("/api", adminRoutes);
app.use("/api", cvRoutes);

// Static dosya servisi (opsiyonel, prod için)
const staticDir = process.env.STATIC_DIR || path.join(__dirname, "../../dist");
if (staticDir) {
  app.use(express.static(staticDir));
  app.get("*", (_req, res) => {
    res.sendFile(path.join(staticDir, "index.html"));
  });
}

// MongoDB bağlantısı
const mongoUri = process.env.MONGO_URI;
if (!mongoUri) {
  // eslint-disable-next-line no-console
  console.error("MONGO_URI çevresel değişkeni tanımlı değil. Lütfen server/.env dosyanızı kontrol edin.");
  process.exit(1);
}

mongoose
  .connect(mongoUri)
  .then(() => {
    // eslint-disable-next-line no-console
    console.log("MongoDB'ye bağlandı.");
    app.listen(PORT, () => {
      // eslint-disable-next-line no-console
      console.log(`Server çalışıyor: http://localhost:${PORT}`);
    });
  })
  .catch((err) => {
    // eslint-disable-next-line no-console
    console.error("MongoDB bağlantı hatası:", err);
    process.exit(1);
  });