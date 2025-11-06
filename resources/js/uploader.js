/**
 * Modern File Upload Handler using Dropzone
 * Replaces jQuery file upload functionality
 */

import Dropzone from 'dropzone';
import 'dropzone/dist/dropzone.css';

export class FileUploader {
    constructor(element, options = {}) {
        this.element = element;
        this.options = {
            url: options.url || '/upload',
            maxFilesize: options.maxFilesize || 10, // MB
            maxFiles: options.maxFiles || 5,
            acceptedFiles: options.acceptedFiles || 'image/*,application/pdf,.doc,.docx,.xls,.xlsx',
            dictDefaultMessage: options.dictDefaultMessage || 'Drop files here or click to upload',
            onSuccess: options.onSuccess || (() => {}),
            onError: options.onError || (() => {}),
            onAddedFile: options.onAddedFile || (() => {}),
            ...options
        };
        
        this.dropzone = null;
        this.init();
    }

    init() {
        // Prevent Dropzone from auto-discovering
        Dropzone.autoDiscover = false;
        
        this.dropzone = new Dropzone(this.element, {
            url: this.options.url,
            maxFilesize: this.options.maxFilesize,
            maxFiles: this.options.maxFiles,
            acceptedFiles: this.options.acceptedFiles,
            dictDefaultMessage: this.options.dictDefaultMessage,
            addRemoveLinks: true,
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            init: () => {
                this.dropzone.on('success', (file, response) => {
                    this.options.onSuccess(file, response);
                });
                
                this.dropzone.on('error', (file, errorMessage) => {
                    this.options.onError(file, errorMessage);
                });
                
                this.dropzone.on('addedfile', (file) => {
                    this.options.onAddedFile(file);
                });
            }
        });
    }

    removeAllFiles() {
        this.dropzone.removeAllFiles();
    }

    disable() {
        this.dropzone.disable();
    }

    enable() {
        this.dropzone.enable();
    }
}

export default FileUploader;
