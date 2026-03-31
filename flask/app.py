from flask import Flask, request, jsonify, send_from_directory
from flask_cors import CORS
import pickle
import pandas as pd
import os

app = Flask(__name__, static_folder='.')
CORS(app)  # Allow requests from XAMPP (localhost:80)

# ── Load models ──────────────────────────────────────────────
BASE = os.path.dirname(os.path.abspath(__file__))

with open(os.path.join(BASE, 'model-2.pkl'), 'rb') as f:
    model = pickle.load(f)          # XGBoost / regression → price

with open(os.path.join(BASE, 'logistic_model.pkl'), 'rb') as f:
    lmodel = pickle.load(f)         # Logistic → price category

with open(os.path.join(BASE, 'scaler.pkl'), 'rb') as f:
    scaler = pickle.load(f)         # StandardScaler


# ── Helper ───────────────────────────────────────────────────
def build_input(data):
    fuel_type      = data.get('fuel_type', 'Petrol')
    seller_type    = data.get('seller_type', 'Dealer')
    transmission   = data.get('transmission', 'Manual')

    row = {
        'Year':                   int(data['year']),
        'Kms_Driven':             float(data['kms_driven']),
        'Owner':                  int(data['owner']),
        'Present_Price_USD':      float(data['present_price']),
        'Fuel_Type_Diesel':       1 if fuel_type == 'Diesel' else 0,
        'Fuel_Type_Petrol':       1 if fuel_type == 'Petrol' else 0,
        'Seller_Type_Individual': 1 if seller_type == 'Individual' else 0,
        'Transmission_Manual':    1 if transmission == 'Manual' else 0,
    }

    df = pd.DataFrame([row])

    # Scale numeric features (same columns as training)
    numeric_cols = ['Kms_Driven', 'Present_Price_USD', 'Owner']
    df[numeric_cols] = scaler.transform(df[numeric_cols])

    return df


# ── Routes ───────────────────────────────────────────────────
@app.route('/')
def index():
    return send_from_directory('.', 'predictor.html')


@app.route('/predict/price', methods=['POST'])
def predict_price():
    try:
        data = request.get_json(force=True)
        df   = build_input(data)
        pred = float(model.predict(df)[0])
        pred = max(0, round(pred, 2))
        return jsonify({'success': True, 'price': pred})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/predict/category', methods=['POST'])
def predict_category():
    try:
        data     = request.get_json(force=True)
        df       = build_input(data)
        category = str(lmodel.predict(df)[0])
        return jsonify({'success': True, 'category': category})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


@app.route('/predict/both', methods=['POST'])
def predict_both():
    """Single endpoint that returns price + category together."""
    try:
        data     = request.get_json(force=True)
        df       = build_input(data)
        price    = float(model.predict(df)[0])
        price    = max(0, round(price, 2))
        category = str(lmodel.predict(df)[0])
        return jsonify({'success': True, 'price': price, 'category': category})
    except Exception as e:
        return jsonify({'success': False, 'error': str(e)}), 500


# ── Run ──────────────────────────────────────────────────────
if __name__ == '__main__':
    # Port 5000 — accessible at http://localhost:5000
    # XAMPP runs on port 80, so these don't conflict.
    app.run(debug=True, port=5000, host='0.0.0.0')
