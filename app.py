from flask import Flask, request, jsonify
import os
os.environ['TF_CPP_MIN_LOG_LEVEL'] = '2'
import tensorflow as tf
from tensorflow.keras.models import load_model
from tensorflow.keras.preprocessing.image import img_to_array
from PIL import Image
import numpy as np


app = Flask(__name__)

def get_model():
    model_path = 'model_architecture_epoch20_16.h5' 
    try:
        model = load_model(model_path)
        print(f"Model {model_path} loaded successfully!")
        return model
    except Exception as e:
        print(f"Error loading model: {str(e)}")
        raise 

# model = get_model()
# print(model.summary())

def preprocess_image(image, target_size):
    if image.mode != "RGB":
        image = image.convert("RGB")
    image = image.resize(target_size)
    image = img_to_array(image)
    image = np.expand_dims(image, axis=0)
    image = image / 255.0  # Normalization
    return image

@app.route('/predict', methods=['POST'])
def predict():
    if 'image' not in request.files:
        return jsonify({'error': 'Image file none'})

    file = request.files['image']
    if file.filename == '':
        return jsonify({'error': 'No selected file'})

    try:
        img = Image.open(file)
        processed_img = preprocess_image(img, target_size=(128, 128))

        model = get_model()

        if processed_img.shape == (1, 128, 128, 3):
            prediction = model.predict(processed_img).tolist()

            response = {
                'prediction': {
                    'fake': prediction[0][0],
                    'real': prediction[0][1]
                }
            }

            return jsonify(response)
        else:
            return jsonify({'error': 'Input image shape doesn\'t match model input shape.'})

    except Exception as e:
        return jsonify({'error': str(e)})


@app.route('/')
def index():
    return "Welcome to Deep Fake Images Prediction", 200

if __name__ == '__main__':
    app.run(port=5000)
