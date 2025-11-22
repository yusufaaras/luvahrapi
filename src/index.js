var express = require("express");
var dotenv = require("dotenv");
dotenv.config();
var mongoose = require("mongoose");
var path = require("path");
var cors = require("cors");
var morgan = require("morgan");
var adminModule = require("./routes/admin");
var adminRoutes = adminModule && adminModule.default ? adminModule.default : adminModule;
// cv route'u require ile alıyoruz; export default kullanıldıysa default altındaki değeri alın
var cvModule = require("./routes/cv");
var cvRoutes = cvModule && cvModule.default ? cvModule.default : cvModule;
var app = express();
var PORT = process.env.PORT ? parseInt(process.env.PORT) : 5000;
app.use(cors());
app.use(morgan("tiny"));
// health-check
app.get("/health", function (_req, res) { return res.json({ ok: true }); });
// API router
app.use("/api", adminRoutes);
app.use("/api", cvRoutes);
// Static dosya servisi (opsiyonel, prod için)
var staticDir = process.env.STATIC_DIR || path.join(__dirname, "../../dist");
if (staticDir) {
    app.use(express.static(staticDir));
    app.get("*", function (_req, res) {
        res.sendFile(path.join(staticDir, "index.html"));
    });
}
// MongoDB bağlantısı
var mongoUri = process.env.MONGO_URI;
if (!mongoUri) {
    // eslint-disable-next-line no-console
    console.error("MONGO_URI çevresel değişkeni tanımlı değil. Lütfen server/.env dosyanızı kontrol edin.");
    process.exit(1);
}
mongoose
    .connect(mongoUri)
    .then(function () {
    // eslint-disable-next-line no-console
    console.log("MongoDB'ye bağlandı.");
    app.listen(PORT, function () {
        // eslint-disable-next-line no-console
        console.log("Server \u00E7al\u0131\u015F\u0131yor: http://localhost:".concat(PORT));
    });
})
    .catch(function (err) {
    // eslint-disable-next-line no-console
    console.error("MongoDB bağlantı hatası:", err);
    process.exit(1);
});
