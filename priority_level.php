<form method="POST" enctype="multipart/form-data">
    <label>Category: <em>(Kategorya)</em></label>
    <select name="category_id" required>
        <?php
        $stmt = $conn->query("SELECT category_id, category_name FROM categories");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<option value='{$row['category_id']}'>{$row['category_name']}</option>";
        }
        ?>
    </select>

    <label>Urgency Level: <em>(Antas ng Dali)</em></label>
    <select name="urgency" required>
        <option value="Low">Low</option>
        <option value="Medium">Medium</option>
        <option value="High">High</option>
    </select>

    <label>Purok: <em>(Purok)</em></label>
    <select name="purok" required>
        <?php for ($i = 1; $i <= 7; $i++): ?>
            <option value="Purok <?= $i ?>">Purok <?= $i ?></option>
        <?php endfor; ?>
    </select>

    <label>Landmark (optional): <em>(Landmark (opsyonal))</em></label>
    <input type="text" name="landmark">

    <label>Select Location on Map: <em>(Piliin ang Lokasyon sa Mapa)</em></label>
    <div id="map"></div>

    <label>Latitude: <em>(Latitude)</em></label>
    <input type="text" name="latitude" id="latitude" required readonly>

    <label>Longitude: <em>(Longitude)</em></label>
    <input type="text" name="longitude" id="longitude" required readonly>

    <h3>Victims (optional): <em>(Mga Biktima)</em></h3>
    <div id="victims">
        <div class="victim-fields">
            <label>Victim Name: <em>(Pangalan ng Biktima)</em></label>
            <input type="text" name="victim_name[]">
            <label>Victim Age: <em>(Edad ng Biktima)</em></label>
            <input type="text" name="victim_age[]">
            <label>Victim Contact: <em>(Kontak ng Biktima)</em></label>
            <input type="text" name="victim_contact[]">
        </div>
    </div>
    <button type="button" onclick="addVictim()">Add More Victim</button>

    <h3>Perpetrators (optional): <em>(Mga Salarin)</em></h3>
    <div id="perpetrators">
        <div class="perpetrator-fields">
            <label>Perpetrator Name: <em>(Pangalan ng Salarin)</em></label>
            <input type="text" name="perpetrator_name[]">
            <label>Perpetrator Age: <em>(Edad ng Salarin)</em></label>
            <input type="text" name="perpetrator_age[]">
            <label>Perpetrator Contact: <em>(Kontak ng Salarin)</em></label>
            <input type="text" name="perpetrator_contact[]">
        </div>
    </div>
    <button type="button" onclick="addPerpetrator()">Add More Perpetrator</button>

    <h3>Witnesses (optional): <em>(Mga Magsusaksi)</em></h3>
    <div id="witnesses">
        <div class="witness-fields">
            <label>Witness Name: <em>(Pangalan ng Magsusaksi)</em></label>
            <input type="text" name="witness_name[]">
            <label>Witness Contact: <em>(Kontak ng Magsusaksi)</em></label>
            <input type="text" name="witness_contact[]">
        </div>
    </div>
    <button type="button" onclick="addWitness()">Add More Witness</button>

    <h3>Incident Details: <em>(Detalye ng Insidente)</em></h3>
    <textarea name="details" rows="4" required></textarea>

    <?php if ($userType === 'official'): ?>
        <label>Priority Level: <em>(Antas ng Pag-prioridad)</em></label>
        <select name="priority_level">
            <option value="Low">Low</option>
            <option value="Medium">Medium</option>
            <option value="High">High</option>
        </select>
    <?php endif; ?>

    <h3>Upload Evidence (optional): <em>(Mag-upload ng Ebidensya)</em></h3>
    <input type="file" name="evidence[]" accept="image/*,video/*" multiple>

    <button type="submit">Submit Report</button>

    <button type="button" onclick="cancelReport()">Cancel <em>(Kanselahin)</em></button>
</form>
