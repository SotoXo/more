"""Compute frozen RAG metrics and SQL/NumPy exact-search agreement from private evidence."""
from __future__ import annotations
import argparse
import hashlib
import json
from pathlib import Path
import numpy as np
from benchmark_embeddings import metric_row, aggregate, percentile


def main() -> int:
    parser = argparse.ArgumentParser(description=__doc__)
    parser.add_argument('--workspace', required=True)
    args = parser.parse_args()
    root = Path(args.workspace); private = root/'storage/app/ai-semantic'
    resource = root/'services/mati-ai/resources/rag'
    protocol = json.loads((resource/'benchmark-protocol-v1.json').read_text())
    results = json.loads((private/'retrieval-results.json').read_text())
    if results['protocol_sha256'] != hashlib.sha256((resource/'benchmark-protocol-v1.json').read_bytes()).hexdigest():
        raise ValueError('Frozen protocol mismatch')
    for name in ('corpus', 'evaluation'):
        if hashlib.sha256((root/protocol[name+'_path']).read_bytes()).hexdigest() != protocol[name+'_sha256']:
            raise ValueError('Frozen input mismatch')
    evaluation = json.loads((root/protocol['evaluation_path']).read_text())
    corpus = json.loads((root/protocol['corpus_path']).read_text())
    queries = {row['id']: row for row in evaluation['queries']+evaluation['negatives']}
    frozen_chunks = {chunk['id']: (doc,chunk) for doc in corpus['documents'] for chunk in doc['chunks']}
    summaries = {}; gate = protocol['rag_gate']
    for mode in ('vector','hybrid'):
        cases = [row for row in results['cases'] if row['mode']==mode]
        positives = [row for row in cases if not row['negative']]
        negatives = [row for row in cases if row['negative']]
        rows=[]; provenance_errors=[]
        for case in positives:
            row = {'language':case['language'],'type':case['type'], 'query_id':case['query_id'],
                   'metrics':metric_row([chunk['chunk_key'] for chunk in case['result']['chunks']],queries[case['query_id']]['relevance'])}
            rows.append(row)
        for case in cases:
            for chunk in case['result']['chunks']:
                doc, source_chunk = frozen_chunks[chunk['chunk_key']]
                if any(chunk.get(key)!=expected for key,expected in {
                    'document_key':doc['id'],'source':doc['source'],'source_version':doc['source_version'],
                    'corpus_version':corpus['corpus_version'],'section':source_chunk['section'],
                    'content_hash':source_chunk['content_hash']}.items()):
                    provenance_errors.append(case['query_id'])
        timing={key:{'p50':percentile([c['result']['timing'][key] for c in positives if key in c['result']['timing']],.5),
                     'p95':percentile([c['result']['timing'][key] for c in positives if key in c['result']['timing']],.95)}
                for key in ('embed_ms','search_ms','total_ms')}
        abstained=[c['query_id'] for c in negatives if c['result']['status']=='insufficient_evidence' and not c['result']['chunks']]
        dynamic=[c for c in negatives if queries[c['query_id']]['expected_policy']=='requires_tool']
        coverage=sum(bool(c['result']['chunks']) for c in positives)/len(positives)
        summary={'metrics':aggregate(rows),'by_language':{lang:aggregate([r for r in rows if r['language']==lang]) for lang in sorted({r['language'] for r in rows})},
                 'by_type':{kind:aggregate([r for r in rows if r['type']==kind]) for kind in sorted({r['type'] for r in rows})},
                 'positive_coverage':coverage,'negative_abstention':len(abstained)/len(negatives),'negative_abstained_ids':abstained,
                 'negative_false_retrieval_ids':[c['query_id'] for c in negatives if c['query_id'] not in abstained],
                 'dynamic_policy_correct':all(c['result']['tool_required'] and c['query_id'] in abstained for c in dynamic),
                 'provenance_errors':provenance_errors,'timing_ms':timing,'query_metrics':rows}
        checks={'recall_at_3':summary['metrics']['recall_at_3']>=gate['recall_at_3_min'],
                'mrr':summary['metrics']['mrr']>=gate['mrr_min'],'ndcg_at_3':summary['metrics']['ndcg_at_3']>=gate['ndcg_at_3_min'],
                'positive_coverage':coverage>=gate['positive_coverage_min'],'negative_abstention':summary['negative_abstention']>=gate['negative_abstention_min'],
                'dynamic_policy':summary['dynamic_policy_correct'],'provenance':not provenance_errors,
                'latency':timing['total_ms']['p95'] is not None and timing['total_ms']['p95']<=gate['end_to_end_warm_p95_ms_max'],
                'no_unavailable':all(c['result'].get('reason')!='RAG_UNAVAILABLE' for c in cases),
                'filters':all(results['filters'].values())}
        summary['gate_checks']=checks;summary['retrieval_gate_pass']=all(checks.values());summaries[mode]=summary
    sql=json.loads((private/'sql-numpy-inputs.json').read_text())
    ids=[row['chunk_key'] for row in sql['chunks']]
    matrix=np.asarray([row['vector'] for row in sql['chunks']],dtype=np.float64)
    matrix=matrix/np.linalg.norm(matrix,axis=1,keepdims=True)
    comparisons=[]
    for query in sql['queries']:
        vector=np.asarray(query['vector'],dtype=np.float64);vector=vector/np.linalg.norm(vector)
        scores=matrix@vector; order=sorted(range(len(ids)),key=lambda i:(-float(scores[i]),i))
        sql_ranked=query['sql_ranked']; numpy_ids=[ids[i] for i in order]; sql_ids=[row['chunk_key'] for row in sql_ranked]
        errors=[abs(row['similarity']-float(scores[ids.index(row['chunk_key'])])) for row in sql_ranked]
        exact=sql_ids[:3]==numpy_ids[:3]
        tie_compatible=all(sql_ids[rank]==numpy_ids[rank] or abs(float(scores[ids.index(sql_ids[rank])])-float(scores[ids.index(numpy_ids[rank])]))<=1e-5 for rank in range(3))
        comparisons.append({'query_id':query['query_id'],'top3_exact':exact,'top3_pass_except_ties':tie_compatible,'max_cosine_error':max(errors),
                            'sql_top3':sql_ids[:3],'numpy_top3':numpy_ids[:3]})
    sql_summary={'queries':len(comparisons),'top3_exact':sum(c['top3_exact'] for c in comparisons),
                 'all_top3_pass_except_ties':bool(comparisons) and all(c['top3_pass_except_ties'] for c in comparisons),
                 'max_cosine_error':max(c['max_cosine_error'] for c in comparisons),'cases':comparisons,'tie_tolerance':1e-5}
    output={'schema_version':'mati-rag-summary-1','protocol_sha256':results['protocol_sha256'],'model':results['model'],
            'counts':results['counts'],'modes':summaries,'sql_numpy':sql_summary,
            'http_statuses':{str(code):sum(r['status']==code for r in results['http']) for code in sorted({r['status'] for r in results['http']})},
            'camisa_blusa':[r for r in results['cases'] if r['query_id']=='q029'],
            'requires_independent_persistence_evidence':True}
    path=private/'retrieval-summary.json';path.write_text(json.dumps(output,ensure_ascii=False,indent=2)+'\n');path.chmod(0o600)
    print(json.dumps({key:value for key,value in output.items() if key not in {'modes','camisa_blusa'}},ensure_ascii=False))
    for mode,summary in summaries.items():print(mode,json.dumps({key:value for key,value in summary.items() if key not in {'query_metrics','by_type','by_language'}},ensure_ascii=False))
    return 0

if __name__=='__main__':
    raise SystemExit(main())
