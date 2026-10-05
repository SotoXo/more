import os,secrets,subprocess,json,time
from pathlib import Path
root=Path('/home/soto/Code/more')
out=root/'storage/app/ai-audit'
env=os.environ.copy()
env.update(MATI_AI_TOKEN=secrets.token_urlsafe(32),MATI_AI_LOCAL_FILES_ONLY='true',MATI_AI_OCR_DOWNLOAD_ENABLED='false',MATI_AI_DEVICE='auto',MATI_AI_MODEL_PROVIDER='siglip',MATI_AI_EXTERNAL_SOURCES_FILE=str(root/'services/mati-ai/config/external-sources.example.json'))
log=(out/'fastapi-http.log').open('w')
p=subprocess.Popen([str(root/'services/mati-ai/.venv/bin/python'),'-m','uvicorn','mati_ai.main:app','--host','127.0.0.1','--port','8001','--workers','1','--no-access-log','--log-level','info'],cwd=root/'services/mati-ai',env=env,stdout=log,stderr=log,start_new_session=True)
# Secret only in private temporary process config; not in audit results.
secret=Path('/tmp/mati-audit-http-secret.json')
secret.write_text(json.dumps({'token':env['MATI_AI_TOKEN'],'pid':p.pid,'port':8001}))
secret.chmod(0o600)
(out/'http_runtime.json').write_text(json.dumps({'pid':p.pid,'port':8001,'bound_host':'127.0.0.1','temporary_configuration':True,'production_environment_modified':False,'token':'configured_ephemeral_redacted'},indent=2))
print('FastAPI started PID',p.pid,'on loopback 8001')
