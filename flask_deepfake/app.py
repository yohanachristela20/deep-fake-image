from flask import Flask, request, jsonify
import os
# os.environ['TF_CPP_MIN_LOG_LEVEL'] = '2'
import tensorflow as tf
from tensorflow.keras.models import Sequential
from tensorflow.keras import layers
from tensorflow.keras.preprocessing.image import img_to_array
from tensorflow.keras.applications import ResNet50
from tensorflow.keras.optimizers import Adam
from PIL import Image
import numpy as np

print(f"TensorFlow version: {tf.__version__}")

app = Flask(__name__)

input_shape = (128, 128, 3)
batch_size = 16

def init_model():
    resnet = ResNet50(weights='imagenet', include_top=False, input_shape=input_shape)
    model = Sequential([
        resnet,
        layers.GlobalAveragePooling2D(),
        layers.Dense(512, activation='relu'),
        layers.BatchNormalization(),
        layers.Dense(2, activation='softmax')
    ])
    model.compile(optimizer=Adam(learning_rate=0.001), loss='categorical_crossentropy', metrics=['accuracy'])
    return model

trained_model = init_model()
trained_model.build((None, *input_shape))
trained_model.load_weights('model_bs16_ep10.h5')

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

        if processed_img.shape == (1, 128, 128, 3):
            predictions = trained_model.predict(processed_img)
            fake_prob, real_prob = predictions[0]
            response = {
                'prediction': {
                    'fake': float(fake_prob),
                    'real': float(real_prob)
                },
                'accuracy': f"{100 * max(fake_prob, real_prob):.2f}%"
            }
            return jsonify(response)
        else:
            return jsonify({'error': 'Input image shape doesn\'t match model input shape.'})

    except Exception as e:
        return jsonify({'error': str(e)})

@app.route('/')
def index():
    return "Welcome to Deep Fake Images Prediction", 200

# if __name__ == '__main__':
#     app.run(port=5000)